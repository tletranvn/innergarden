<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Écrivez votre commentaire ici...',
                    'rows' => 5,
                    'class' => 'form-control rounded-3 shadow-sm',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez saisir votre commentaire.'),
                    new Assert\Length(
                        min: 10,
                        max: 500,
                        minMessage: 'Votre commentaire doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Votre commentaire ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
