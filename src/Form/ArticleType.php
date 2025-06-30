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
use Symfony\Component\Validator\Constraints as Assert;

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
            ->add('imageUrl', TextType::class, [
                'label' => 'URL de l\'image',
                'required' => false,
                'constraints' => [
                    new Assert\Url(message: 'L\'URL de l\'image n\'est pas valide.'),
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
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer l\'article',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
