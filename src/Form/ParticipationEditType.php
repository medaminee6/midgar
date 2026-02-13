<?php

namespace App\Form;

use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParticipationEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de la participation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez votre participation',
                    'rows' => 5,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est requise.']),
                    new Assert\Length(['min' => 5, 'minMessage' => 'Minimum 5 caractères.']),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (peinture, dessin)',
                'mapped' => true,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ])
            ->add('artworkId', IntegerType::class, [
                'label' => 'ID de l\'œuvre (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
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
            'data_class' => Participation::class,
        ]);
    }
}
