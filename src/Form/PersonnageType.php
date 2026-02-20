<?php
namespace App\Form;

use App\Entity\Personnage;
use App\Entity\Universe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PersonnageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length(['min' => 2, 'max' => 255, 'minMessage' => 'Le nom doit contenir au moins 2 caractères'])
                ]
            ])
            ->add('universe', EntityType::class, [
                'class' => Universe::class,
                'choice_label' => 'name',
                'label' => 'Univers',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un univers'])
                ]
            ])
            ->add('classRole', ChoiceType::class, [
                'label' => 'Classe / Rôle',
                'choices' => [
                    'Guerrier' => 'warrior',
                    'Mage' => 'mage',
                    'Escroc' => 'rogue',
                    'Clerc' => 'cleric',
                    'Rôdeur' => 'ranger',
                    'Autre' => 'other',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une classe/rôle'])
                ]
            ])
            ->add('strength', IntegerType::class, [
                'label' => 'Force',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La force doit être comprise entre {{ min }} et {{ max }}.'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '0-100'
                ]
            ])
            ->add('agility', IntegerType::class, [
                'label' => 'Agilité',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'L\'agilité doit être comprise entre {{ min }} et {{ max }}.'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '0-100'
                ]
            ])
            ->add('magic', IntegerType::class, [
                'label' => 'Magie',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La magie doit être comprise entre {{ min }} et {{ max }}.'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '0-100'
                ]
            ])
            ->add('defense', IntegerType::class, [
                'label' => 'Défense',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La défense doit être comprise entre {{ min }} et {{ max }}.'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '0-100'
                ]
            ])
            ->add('historyContext', TextareaType::class, [
                'label' => 'Historique & Contexte',
                'constraints' => [
                    new NotBlank(['message' => 'L\'historique est obligatoire']),
                    new Length(['min' => 30, 'minMessage' => 'L\'historique doit contenir au moins 30 caractères'])
                ]
            ])
            ->add('abilitiesPowers', TextareaType::class, [
                'label' => 'Capacités & Pouvoirs', 
                'required' => false
            ])
            ->add('tags', TextType::class, [
                'label' => 'Tags',
                'constraints' => [
                    new NotBlank(['message' => 'Les tags sont obligatoires']),
                ]
            ])
            ->add('portraitFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez sélectionner une image valide (JPEG, PNG, GIF, WebP)'
                    ])
                ],
                'label' => 'Portrait (image)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }
}
