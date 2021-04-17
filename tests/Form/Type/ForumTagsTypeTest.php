<?php

namespace App\Tests\Form\Type;

use App\DataObject\ForumTagData;
use App\Form\DataTransformer\ForumTagDataListToStringsTransformer;
use App\Form\DataTransformer\TagArrayToStringTransformer;
use App\Form\Type\ForumTagsType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\ForumTagsType
 */
class ForumTagsTypeTest extends TypeTestCase {
    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    private $form;

    protected function setUp(): void {
        parent::setUp();

        $this->form = $this->factory->create(ForumTagsType::class);
    }

    protected function getExtensions(): array {
        return [
            new PreloadedExtension([
                new ForumTagsType(
                    new TagArrayToStringTransformer(),
                    new ForumTagDataListToStringsTransformer()
                ),
            ], [])
        ];
    }

    public function testSubmit(): void {
        $data = $this->form->submit('foo, bAr')->getData();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEqualsCanonicalizing(
            ['bAr', 'foo'],
            array_map(static function (ForumTagData $tag) {
                return $tag->getName();
            }, $data)
        );
    }

    public function testView(): void {
        $tagData1 = new ForumTagData();
        $tagData1->setName('foo');

        $tagData2 = new ForumTagData();
        $tagData2->setName('bar');

        $viewData = $this->form->setData([$tagData1, $tagData2])->getViewData();

        $this->assertSame('bar, foo', $viewData);
    }
}
