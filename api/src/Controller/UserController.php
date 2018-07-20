<?php

namespace App\Controller;

use App\Utils\MailerDispatcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Form\Type\ResettingFormType;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/user")
 */
class UserController extends FOSRestController
{
    /**
     * 'data' is a required wrapper for websanova auth library
     *
     * @Rest\View()
     * @Rest\Get()
     * @return array|View
     */
    public function getCurrentUser()
    {
        return [ 'data' => $this->getUser() ];
    }

    /**
     * @Rest\View()
     * @Rest\Post("/email", name="edit_user")
     * @param Request $request
     * @param MailerDispatcherInterface $mailerDispatcher
     * @param UserManagerInterface $userManager
     * @return FormInterface|View
     */
    public function editUserEmail(
        Request $request,
        MailerDispatcherInterface $mailerDispatcher,
        UserManagerInterface $userManager
    ) {
        $user = $this->getUser();
        $form = $this->createFormBuilder($user, ['validation_groups' => ["changeEmail"]])
            ->add('tempEmail', EmailType::class)
            ->getForm();

        $form->submit($request->request->getIterator()->getArrayCopy());

        if ($form->isValid()) {
            $tmpUser = clone $user;
            $tmpUser->setEmail($user->getTempEmail());
            $mailerDispatcher->sendEmailConfirmation($tmpUser);
            $user->setConfirmationToken($tmpUser->getConfirmationToken());
            $userManager->updateUser($user);
            return $this->view(null, 200);
        }

        return $form;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/password")
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @return View|FormInterface
     */
    public function editUserPassword(Request $request, UserManagerInterface $userManager)
    {
        $user = $this->getUser();
        $form = $this->createForm(ResettingFormType::class, $user);

        $form->submit($request->request->getIterator()->getArrayCopy());

        if ($form->isValid()) {
            $userManager->updatePassword($user);
            return $this->view(null, 200);
        }

        return $form;
    }
}
