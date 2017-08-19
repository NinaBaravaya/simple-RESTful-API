<?php
namespace AppBundle\Controller;

use AppBundle\Form\AddUserForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends Controller
{
    /**
     * @Route("/users/{id}", name="show_one_user")
     * @Method("GET")
     */
    public function showUser($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')
            ->findOneBy(['id' => $id]);
        if (!$user) {
            throw $this->createNotFoundException(sprintf(
                'No user found with id "%s"',
                $id
            ));
        }
        $data = $this->container->get('jms_serializer')
            ->serialize($user, 'json');

        $response = new Response($data,200);
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    /**
     * @Route("/users/new", name="new_user")
     * @Method("POST")
     */
    public function newUser(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new HttpException(
                400,
                sprintf('Invalid JSON: '.$request->getContent())
            );
        }
        //create form object
        $form = $this->createForm(AddUserForm::class);

        $form->submit($data);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $form->getData();

            $validator = $this->get('validator');
            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                $data = $this->container->get('jms_serializer')
                    ->serialize($errors, 'json');

                return new Response($data);
            }
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $id = $user->getId();

        }
        $result = array(
            'msg' => 'User created',
        );

       $url = $this->generateUrl('show_user', array(
            'id' => $id
        ));
        $response = new JsonResponse($result,201);
        $response->headers->set('Location', $url);
        return $response;
    }

    /**
     * @Route("/users/{id}/edit", name="edit_user")
     * @Method("PATCH")
     */
    public function editUser($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')
            ->findOneBy(['id' => $id]);
        if (!$user) {
            throw $this->createNotFoundException(sprintf(
                'No user found with id "%s"',
                $id
            ));
        }
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new HttpException(
                400,
                sprintf('Invalid JSON: '.$request->getContent())
            );
        }

        $form = $this->createForm(AddUserForm::class, $user);
        $form->submit($data);

        if ($form->isSubmitted()) {
            $user = $form->getData();

            $validator = $this->get('validator');
            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                $data = $this->container->get('jms_serializer')
                    ->serialize($errors, 'json');

                return new Response($data, 200);
            }
            $em->persist($user);
            $em->flush();
        }

        $result = array(
            'msg' => 'User updated',
        );

        return new JsonResponse($result);
    }

    /**
     * @Route("/users", name="show_user")
     * @Method("GET")
     */
    public function showUsers()
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:User')->findBy(array('isDeleted' => NULL));//repository object

        $data = $this->container->get('jms_serializer')
            ->serialize($users, 'json');

        $response = new Response($data,200);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/users/delete", name="delete_user")
     * @Method("DELETE")
     */
    public function deleteUser(Request $request)
    {
        $id = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')
            ->findOneBy(['id' => $id]);
        if($user){
            $user->setIsDeleted('1');
            $em->flush();
        }

        return new Response(null, 204);
    }
}