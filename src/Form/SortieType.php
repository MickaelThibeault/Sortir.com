<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, ['label' => 'Nom de la sortie : ', 'required' => true])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie : ',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'propertyPath' => 'parent.all[dateLimiteInscription].data',
                        'message' => 'La date doit être ultérieure à la date limite d\'inscription'
                    ]),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date doit être ultérieure à maintenant',
                    ]),
                ]
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => 'Date limite d\'inscription : ',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date doit être ultérieure à maintenant',
                    ]),
                ]
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombres de places : ',
                'required' => true
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes) : ',
                'required' => true
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos : ',
                'attr' => ['rows' => 5]
            ])
            ->add('campus', CampusType::class, [
                'label' => false,
            ])
            ->add('lieu', LieuType::class, [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);

    }
}
