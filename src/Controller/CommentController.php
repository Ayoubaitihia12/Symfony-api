<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CommentController extends AbstractController
{

    public function api_add_comment(Post $post , Request $request , EntityManagerInterface $em): Response
    {

        $data = json_decode($request->getContent(), true);

        $comment = new Comment();

        $comment->setContent($data['content']);
        $comment->setPost($post);

        $em->persist($comment);
        $em->flush();

        return new Response('Comment Added');
    }

    public function api_delete_comment($id , EntityManagerInterface $em): Response
    {
        $comment  = $em->getRepository(Comment::class)->find($id);

        $code = 300;
        $message = "";

        if($comment){

            $em->remove($comment);
            $em->flush();

            $message = "Comment deleted";
        }else{
            $message = "Comment not found";
        }

        $data= array(
            "code"=>$code,
            "message"=>$message 
        );

        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($data, 'json');
        return new Response($jsonContent);
    }
}
