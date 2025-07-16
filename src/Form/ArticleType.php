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
use Symfony\Component\Form\Extension\Core\Type\FileType; // NOUVEAU : Importez FileType
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichImageType; // NOUVEAU : Importez VichImageType si nécessaire
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; // NOUVEAU : Import pour le champ publishedAt

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'article',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire.'),
                    new Assert\Length(min: 3, max: 255),
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le contenu ne peut pas être vide.'),
                    new Assert\Length(min: 10),
                ]
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Extrait',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 300),
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
            // ANCIEN : Le champ imageUrl est remplacé par imageFile pour l'upload via VichUploader
            // ->add('imageUrl', TextType::class, [
            //     'label' => 'URL de l\'image',
            //     'required' => false,
            //     'constraints' => [
            //         new Assert\Url(message: 'L\'URL de l\'image n\'est pas valide.'),
            //     ]
            // ])
            // CORRECTION ICI : Utilisez VichImageType::class et RETIREZ 'mapped' => false
            ->add('imageFile', VichImageType::class, [ // IMPORTANT : Utilisez VichImageType
                'label' => 'Image de l\'article (JPG, PNG, GIF)',
                // 'mapped' => false, // CETTE LIGNE DOIT ÊTRE SUPPRIMÉE OU COMMENTÉE !
                'required' => false,
                'allow_delete' => true, // Permet de supprimer l'image existante via un checkbox
                'download_uri' => true, // Affiche un lien pour télécharger l'image
                'image_uri' => true, // Affiche l'image existante si présente
                'asset_helper' => true, // Utilise le helper asset() de Symfony pour générer l'URL de l'image
                'attr' => [
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF).',
                    ])
                ]
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Auteur',
            ])
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
        ]);
    }
}