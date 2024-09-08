<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Groupe;
use App\Entity\Participant;
use App\Entity\Sortie;
use Faker\Provider\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo :',
                'required' => true,

            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom :',
                'required' => true,
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom :',
                'required' => true,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Numéro de téléphone :',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse mail :',
                'invalid_message' => 'email non conforme',
                'required' => true,
            ])
            ->add('motDePasse', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options' => ['label' => 'Mot de passe :'],
                'second_options' => ['label' => 'Veuillez confirmer votre mot de passe :'],
                'required' => false,
                'mapped' => false,
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus',
            ])
            ->add('photo', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'changer ma photo de profil :',
                'constraints' => [
                    new Image(['maxSize' => '5000k'])
                ]
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
