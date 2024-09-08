<?php

namespace App\Form;

use App\Entity\Ville;
use App\Services\VilleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VilleType extends AbstractType
{
    private $villeService;

    public function __construct(VilleService $villeService)
    {
        $this->villeService = $villeService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', ChoiceType::class, [
                'choices' => $this->villeService->getChoices(),
                'label' => 'Ville : ',
            ])
            ->add('codePostal', null, [
                'label' => 'Code Postal :'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ville::class,
        ]);
    }
}
