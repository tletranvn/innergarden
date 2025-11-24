<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Import for file upload
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // Import for publishedAt field

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'article',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire.'),
                    new Assert\Length(
                        min: 3,
                        max: 255,
                        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le contenu ne peut pas être vide.'),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères.'
                    ),
                ]
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extrait',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 300,
                        maxMessage: 'L\'extrait ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ]
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier l\'article',
                'required' => false,
            ])
            // Ajout publishedAt pour la date de publication automatiquement gérée
            ->add('publishedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de publication (optionnelle)',
            ])
            // Ajout du champ 'slug'
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'required' => false, // Permet de laisser le champ vide pour auto-génération
                'attr' => ['placeholder' => 'Laisser vide pour auto-générer (ex: mon-super-article)'],
                'help' => 'L\'identifiant unique dans l\'URL de l\'article.'
            ])
            // Image upload field - direct Cloudinary upload
            ->add('imageFile', FileType::class, [
                'label' => 'Image de l\'article (JPG, PNG, GIF, WebP)',
                'mapped' => false, // Not mapped to entity, handled in controller
                'required' => false,
                'attr' => [
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        mimeTypesMessage: 'Veuillez télécharger une image valide (JPG, PNG, GIF, WebP)'
                    )
                ]
            ])
            // Le champ 'author' n'est plus dans le formulaire - il sera défini automatiquement dans le contrôleur
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'csrf_protection' => false, // TEMPORARY: Disable CSRF for testing
        ]);
    }
}