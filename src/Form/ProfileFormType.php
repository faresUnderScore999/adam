<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['disabled' => true])
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('phone', TextType::class)
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
            ]);

        if ($options['is_psychologist']) {
            $builder
                ->add('diploma', TextType::class)
                ->add('specialty', TextType::class)
                ->add('bio', TextareaType::class, ['attr' => ['rows' => 4]]);
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'New password'],
                'second_options' => ['label' => 'Confirm new password'],
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Passwords must match.',
                'constraints' => [
                    new Assert\Regex('/^$|.{6,}$/', message: 'Password must be at least 6 characters when provided.'),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Update profile']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_psychologist' => false,
        ]);
    }
}
