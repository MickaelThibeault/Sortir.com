<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Services\LieuService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LieuType extends AbstractType
{
    private $lieuService;

    public function __construct(LieuService $lieuService)
    {
        $this->lieuService = $lieuService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ville', VilleType::class, [
                'label' => false
            ])

            ->add('nom', ChoiceType::class, [
                'choices' => $this->lieuService->getChoices(),
                'label' => 'Lieu : ',
                'placeholder' => 'Choisissez un lieu',
                'mapped' => false,
            ])
            ->add('autreNom', TextType::class, [
                'label' => 'Lieu :',
                'mapped' => false,
            ])
            ->add('rue', null, [
                'label' => 'Rue :'
            ])
            ->add('latitude', null, [
                'label' => 'Latitude : '
            ])
            ->add('longitude', null, [
                'label' => 'Longitude : '
            ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class,
        ]);
    }
}
