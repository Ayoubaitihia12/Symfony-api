<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Form\PostType;
use App\Repository\CommentRepository;

class PostController extends AbstractController
{
    public function posts_list(PostRepository $postRepository , Request $request): Response
    {        
        $posts = $postRepository->findAll();

        $postList = [];

        foreach($posts as $post){
            $m['id'] = $post->getId();
            $m['title'] = $post->getTitle();
            $m['content'] = $post->getContent();

            $postList[] =$m; 
        }

        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($postList, 'json');
        return new Response($jsonContent);     
    }

    public function post_details(Post $post , CommentRepository $commentRepository)
    {
        $postList = [];

        $m['id'] = $post->getId();
        $m['title'] = $post->getTitle();
        $m['content'] = $post->getContent();

        $comments = $commentRepository->findBy(['post' => $post]);
        
        $commentList =[];

        foreach($comments as $comment){
            $c['id'] = $comment->getId();
            $c['content'] = $comment->getContent();
            $commentList[] = $c;
        }

        $m['comments'] = $commentList;

        $postList[] = $m;

        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($postList, 'json');
        return new Response($jsonContent);  


    }

    public function comments_list(Post $post , CommentRepository $commentRepository )
    {
        
        $comments = $commentRepository->findBy(['post' => $post]);

        $commentList = [];

        foreach($comments as $comment){
           
            $m['id'] = $comment->getId();
            $m['content'] = $comment->getContent();

            $commentList[] = $m;
        }

        header('Content-Type: application/json'); 
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent=$serializer->serialize($commentList, 'json');
        return new Response($jsonContent);  


    }

    public function show(Post $post, Request $request , EntityManagerInterface $em , CommentRepository $commentRepository):Response
    {
        $comment = new Comment();

        $comments = $commentRepository->findBy(['post' => $post ]);

        $form = $this->createForm(CommentType::class,$comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            
            $comment->setPost($post);

            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('app_post_show',[
                'id' => $post->getId()
            ]);

        }

        return $this->render('post/show.html.twig',[
            'post' => $post,
            'form' => $form->createView(),
            'comments' => $comments
        ]);
    }

    public function add(EntityManagerInterface $em , Request $request):Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class,$post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em->persist($post);
            $em->flush();
        }

        return $this->render('post/add.html.twig',[
            'form' => $form->createView()
        ]);
    }

    public function update(EntityManagerInterface $em , Request $request , Post $post):Response
    {

        $form = $this->createForm(PostType::class,$post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            
            $em->persist($post);
            $em->flush();
        }

        return $this->render('post/update.html.twig',[
            'form' => $form->createView()
        ]);
    }

    public function delete(EntityManagerInterface $em , Post $post , Request $request)
    {
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('app_post_add');
    }

    public function api_add_post(Request $request , EntityManagerInterface $em): Response
    {
        
        $data = json_decode($request->getContent(), true);

        $post = new Post();

        $post->setTitle($data['title']);
        $post->setContent($data['content']);

        $em->persist($post);
        $em->flush();

        return new Response('Post added successfully');

    }

    public function api_delete_post($id , PostRepository $postRepository , EntityManagerInterface $em):Response
    {

        $code = 300;
        $message = "";

        $post = $postRepository->find($id);

        if($post){           
            
            // Remove all comments form Post:
            $comments = $em->getRepository(Comment::class)->findBy(['post' => $post]);

            foreach($comments as $comment){
                $em->remove($comment);
                $em->flush();
            }
            
            //Remove Post:
            $em->remove($post);
            $em->flush();

            $code = 200;
            $message = "Post deleted";

        }else{
            $message = "Post not found";
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
