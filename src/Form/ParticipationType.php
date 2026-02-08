<?php

namespace App\Form;

use App\Entity\Participation;
use App\Entity\Defi;
use App\Enum\StatutParticipation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de la participation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez votre participation',
                    'rows' => 5
                ]
            ])
            ->add('dateSoumission', DateTimeType::class, [
                'label' => 'Date de soumission',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutParticipation::class,
                'label' => 'Statut',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('userId', IntegerType::class, [
                'label' => 'Utilisateur ID',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('artworkId', IntegerType::class, [
                'label' => 'Artwork ID',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('defi', EntityType::class, [
                'class' => Defi::class,
                'choice_label' => 'titre',
                'label' => 'Défi',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}
