<?php

namespace App\Validator;

use App\Security\Authentication;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RateLimitValidator extends ConstraintValidator {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $ipWhitelist;

    public function __construct(
        Authentication $authentication,
        EntityManagerInterface $manager,
        RequestStack $requestStack,
        array $ipWhitelist
    ) {
        $this->authentication = $authentication;
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        // FIXME: $ipWhitelist shouldn't contain null values
        $this->ipWhitelist = array_filter($ipWhitelist, 'is_string');
    }

    public function validate($value, Constraint $constraint): void {
        if ($value === null) {
            return;
        }

        if (!$constraint instanceof RateLimit) {
            throw new UnexpectedTypeException($constraint, RateLimit::class);
        }

        if (!$constraint->entityClass && !\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!$constraint->ipField && !$constraint->userField) {
            throw new ConstraintDefinitionException(
                'Either the "ip" or "user" fields must be specified'
            );
        }

        $class = $constraint->entityClass ?: \get_class($value);
        $interval = \DateInterval::createFromDateString($constraint->period);
        $time = (new \DateTimeImmutable('@'.time()))->sub($interval);

        $qb = $this->manager->createQueryBuilder()
            ->select('COUNT(e)')
            ->from($class, 'e')
            ->where(sprintf('e.%s >= :timestamp', $constraint->timestampField))
            ->setParameter('timestamp', $time, Types::DATETIMETZ_IMMUTABLE);

        $expr = $qb->expr()->orX();

        if ($constraint->ipField) {
            $request = $this->requestStack->getCurrentRequest();
            $ip = $request ? $request->getClientIp() : null;

            if ($ip && !IpUtils::checkIp($ip, $this->ipWhitelist)) {
                $expr->add("e.{$constraint->ipField} = :ip");

                $qb->setParameter('ip', $ip);
            }
        }

        if ($constraint->userField) {
            $user = $this->authentication->getUser();

            if ($user) {
                $expr->add("e.{$constraint->userField} = :user");

                $qb->setParameter('user', $user);
            }
        }

        $qb->andWhere($expr);

        $count = $qb->getQuery()->getSingleScalarResult();

        if ($count >= $constraint->max) {
            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->errorPath)
                ->setCode(RateLimit::RATE_LIMITED_ERROR)
                ->addViolation();
        }
    }
}
