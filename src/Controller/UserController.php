<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('', name: 'user_index', methods:['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('', name: 'user_create', methods:['POST'])]
    public function create(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'constraints'=>[
                    new Assert\NotBlank(),
                    new Assert\Length(['min'=>1, 'max'=>255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints'=>[
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $form->submit($data);

        if(!($form->isValid())) {
            return new JsonResponse('Invalid form', 400);
        }

        if($data['age'] <= 21){
            return new JsonResponse('Wrong age', 400);
        }

            $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
            if(count($user) !== 0){
                return new JsonResponse('Name already exists', 400);
            }

            $player = new User();
            $player->setName($data['nom']);
            $player->setAge($data['age']);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(
                        $user,
                        201,
                        ['Content-Type' => 'application/json;charset=UTF-8']
                    ); 
    }

    #[Route('/{id}', name: 'user_show', methods:['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {

            $user = $entityManager->getRepository(User::class)->find($id);
            if (!$user) {
                return new JsonResponse('Wrong id', 404);
            }
            
        return $this->json(
            $user,
            200,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );

        return new JsonResponse('Wrong id', 404);
    }

    #[Route('/{id}', name: 'user_update', methods:['PATCH'])]
    public function update(EntityManagerInterface $entityManager,int $id, Request $request): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if(!$user){
            return new JsonResponse('Wrong id', 404);
        }

                $data = json_decode($request->getContent(), true);
                $form = $this->createFormBuilder()
                    ->add('nom', TextType::class, array(
                        'required'=>false
                    ))
                    ->add('age', NumberType::class, [
                        'required' => false
                    ])
                    ->getForm();

                $form->submit($data);

                if(!($form->isValid())) {
                    return new JsonResponse('Invalid form', 400);
                }

                    foreach($data as $key=>$value){
                        switch($key){
                            case 'nom':
                                $user = $entityManager->getRepository(User::class)->findBy(['nom'=>$data['name']]);
                                if(count($user) === 0){
                                    $user[0]->setName($data['nom']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Name already exists', 400);
                                }
                                break;
                            case 'age':
                                if($data['age'] > 21){
                                    $user[0]->setAge($data['age']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Wrong age', 400);
                                }
                                break;
                        }
                    }

            

            return new JsonResponse(array('name'=>$user[0]->getName(), "age"=>$user[0]->getAge(), 'id'=>$user[0]->getId()), 200);

    }

    #[Route('/{id}', name: 'user_delete', methods:['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if(!$user) {
            return new JsonResponse('Wrong id', 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $stillExist = $entityManager->getRepository(User::class)->find($id);

        if(!empty($stillExist)){
            return new \Exception("User not deleted", 500);
        }
        
        return new JsonResponse('', 204);
    }
}
