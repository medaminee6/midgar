<?php
namespace App\Form;

use App\Entity\Universe;
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

class UniverseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length(['min' => 3, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 3 caractères'])
                ]
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Haute Fantaisie' => 'high-fantasy',
                    'Fantaisie Sombre' => 'dark-fantasy',
                    'Science-Fantasy' => 'scifi-fantasy',
                    'Fantaisie Urbaine' => 'urban-fantasy',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un genre'])
                ]
            ])
            ->add('shortDescription', TextType::class, [
                'label' => 'Description courte',
                'constraints' => [
                    new NotBlank(['message' => 'La description courte est obligatoire']),
                    new Length(['min' => 10, 'max' => 500, 'minMessage' => 'La description doit contenir au moins 10 caractères'])
                ]
            ])
            ->add('storyContext', TextareaType::class, [
                'label' => 'Histoire & Contexte',
                'constraints' => [
                    new NotBlank(['message' => 'Le contexte de l\'histoire est obligatoire']),
                    new Length(['min' => 50, 'minMessage' => 'Le contexte doit contenir au moins 50 caractères'])
                ]
            ])
            ->add('themes', TextType::class, [
                'required' => false, 
                'label' => 'Thèmes (séparés par des virgules)', 
                'mapped' => false
            ])
            ->add('bannerFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez sélectionner une image valide (JPEG, PNG, GIF, WebP)'
                    ])
                ],
                'label' => 'Bannière (image)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Universe::class,
        ]);
    }
}
