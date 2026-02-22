<?php

namespace App\Form;

use App\Entity\Artefact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ArtefactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length(['min' => 3, 'minMessage' => 'Le nom doit contenir au moins 3 caractères'])
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Arme' => 'Arme',
                    'Armure' => 'Armure',
                    'Bijoux' => 'Bijoux',
                    'Relique' => 'Relique',
                    'Outil Magique' => 'Outil Magique',
                ],
                'placeholder' => 'Sélectionner un type',
                'constraints' => [new NotBlank(['message' => 'Le type est obligatoire'])],
            ])
            ->add('universe', TextType::class, [
                'label' => 'Univers',
                'constraints' => [
                    new NotBlank(['message' => "L'univers est obligatoire"]),
                    new Length(['min' => 2, 'minMessage' => "L'univers doit contenir au moins 2 caractères"])
                ],
            ])
            ->add('origins', TextareaType::class, [
                'label' => 'Origines & Histoire',
                'constraints' => [new NotBlank(['message' => 'Les origines sont obligatoires'])],
            ])
            ->add('powers', TextareaType::class, [
                'label' => 'Pouvoirs & Propriétés',
                'constraints' => [new NotBlank(['message' => 'Les pouvoirs sont obligatoires'])],
            ])
            ->add('rarity', ChoiceType::class, [
                'label' => 'Rareté',
                'choices' => [
                    'Commune' => 'Commune',
                    'Rare' => 'Rare',
                    'Épique' => 'Épique',
                    'Légendaire' => 'Légendaire',
                    'Mythique' => 'Mythique',
                ],
                'placeholder' => 'Sélectionner une rareté',
                'constraints' => [new NotBlank(['message' => 'La rareté est obligatoire'])],
            ])
            ->add('tag', TextType::class, [
                'label' => 'Tag',
                'constraints' => [new NotBlank(['message' => 'Le tag est obligatoire'])],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez sélectionner une image valide (JPEG, PNG, GIF, WebP)',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artefact::class,
        ]);
    }
}
