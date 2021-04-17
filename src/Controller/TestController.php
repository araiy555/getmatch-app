<?php

namespace App\Controller;

use App\DataObject\SubmissionData;
use App\Entity\Submission;
use App\Entity\User;
use App\Message\NewSubmission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/test", name="test")
     */
    public function index(): Response
    {
        $base_url = 'http://192.168.33.10/login';

        $cookie_path = './cookie.txt';
        touch($cookie_path);
        $data = '_username=araiy&_password=q1w2e3r4';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // post
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // jsonデータを送信

        $put = curl_exec($ch) or die('error ' . curl_error($ch));
        var_dump($put);
        curl_close($ch);

//        $base_url = 'http://192.168.33.10';
//
//
//        $data = [
//            "title" => "arraer",
//            "url" => "",
//            "body" => "fafda",
//            "forum" => 1
//        ];
//
//        $header = [
//            'x-experimental-api: 1',
//            'Content-Type: application/json',
//        ];
//
//        $curl = curl_init();
//
//        curl_setopt($curl, CURLOPT_URL, $base_url.'/api/submissions');
//        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // post
//      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // jsonデータを送信
//        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
//
//        $response = curl_exec($curl);
//
//        var_dump($response);
//
//        curl_close($curl);
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }
}
