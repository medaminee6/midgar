<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParticiperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre participation',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Décrivez votre création, votre approche ou votre message...',
                    'rows' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est requise.']),
                    new Assert\Length(['min' => 5, 'minMessage' => 'Minimum 5 caractères.']),
                ],
            ])
            // image upload removed: users can choose an existing artwork ID or
            // use the painting editor (link provided in the template)
            ->add('artworkId', IntegerType::class, [
                'label' => 'Ou ID d\'une œuvre existante (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex: 1 (si vous avez déjà une œuvre)',
                    'min' => 1,
                ],
                'constraints' => [
                    new Assert\Positive(['message' => 'L\'ID doit être un nombre positif.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
