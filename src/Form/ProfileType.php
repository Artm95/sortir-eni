<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', TextType::class, [
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Pseudo'
            ])
            ->add('firstName', TextType::class, [
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Prénom'
            ])
            ->add('lastName', TextType::class, [
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Nom'
            ])
            ->add('phoneNumber', TextType::class, [
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Téléphone'
            ])
            ->add('email', TextType::class, [
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Email'
            ])
            ->add('plainPassword', PasswordType::class, [
                // 'mapped' => false,
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Mot de passe',
                'required' => false
            ])
            ->add('confirmation', PasswordType::class, [
                // 'mapped' => false,
                // 'label_attr' => [
                //     'class' => '`checkbox-inline'
                // ],
                'label' => 'Confirmation',
                'required' => false
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'label' => 'Campus'
                // 'choice_value' => 'id'
            ])
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image()
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
