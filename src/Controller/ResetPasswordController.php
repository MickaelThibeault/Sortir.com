<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface       $entityManager
    )
    {
    }


    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->entityManager->getRepository(Participant::class)->findOneBy([
                'email' => $email,
            ]);

            //Si le user est trouvé
            if ($user) {
                try {
                    // Générer le token de réinitialisation
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                    // Convertir le token en chaîne
                    $resetTokenString = $resetToken->getToken(); // Assurez-vous que la méthode getToken() existe et est correcte
                    // Stocker le token dans la session
                    $this->setTokenObjectInSession($resetToken);

                    // Rediriger vers la page de réinitialisation du mot de passe avec le token
                    return $this->redirectToRoute('app_reset_password', ['token' => $resetTokenString]);
                } catch (ResetPasswordExceptionInterface $e) {
                    $this->addFlash('reset_password_error', sprintf(
                        '%s - %s',
                        $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                        $translator->trans($e->getReason(), [], 'ResetPasswordBundle')));
                }
            } else {
                $this->addFlash('reset_password_error', 'Email non trouvé');
            }

            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }


    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {

        //S'il y a un token dans l'URL
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        $token = $this->getTokenFromSession();


        try {
            //On récupère l'utilisateur associé au token
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);


        //On a validé le formulaire et on a un mot de passe correct
        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            // On encode le mot de passe et le met à jour
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setMotDePasse($encodedPassword);
            $this->entityManager->flush();

            // Le mot de passe a été changé, on redirige vers la page de connexion
            $this->cleanSessionAfterReset();
            return $this->redirectToRoute('app_home');
        }
        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
