<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Récupérer l'utilisateur à partir des options
        $user = $options['user'];

        $builder
            ->add('dateDebut', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Entre le '
            ])
            ->add('dateFin', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => ' et le '
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',  // Assurez-vous que "nom" est l'attribut que vous souhaitez afficher
                'required' => false,
                'placeholder' => 'Choisir un lieu',
                'label' => 'Campus',
                'data' => $user->getCampus()
            ])
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Le nom de la sortie contient : ',
            ])
            ->add('organisateur', CheckboxType::class, [
                'label' => 'Sorties dont je suis l\'organisateur/trice',
                'required' => false,
                'data' => true,
            ])
            ->add('inscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je suis inscrit/e ',
                'required' => false,
                'data' => true,
            ])
            ->add('pasinscrit', CheckboxType::class, [
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e ',
                'required' => false,
                'data' => true,
            ])
            ->add('passees', CheckboxType::class, [
                'label' => 'Sorties passées ',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',  // Utilisation de GET pour maintenir les filtres dans l'URL
            'csrf_protection' => false,
            // Définir la classe de données utilisée par ce formulaire
            'data_class' => null,
            // Ajouter une option pour passer l'utilisateur
            'user' => null,
        ]);
    }
}
