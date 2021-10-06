<?php

namespace App\Form;

use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => false
            ])
            ->add('from', DateType::class, [
                'label' => "Entre",
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('to', DateType::class, [
                'label' => "Et",
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tous les campus',

            ])
            ->add('organized', CheckboxType::class, [
                'label' => "Sorties dont je suis l'organisteur/trice",
                'required' => false
            ])
            ->add('subscribed', CheckboxType::class, [
                'label' => "Sorties auxquelles je suis inscrit/e",
                'required' => false
            ])
            ->add('notSubscribed', CheckboxType::class, [
                'label' => "Sorties auxquelles je ne suis pas inscrit/e",
                'required' => false
            ])
            ->add('over', CheckboxType::class, [
                'label' => "Sorties passée",
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET'
        ]);
    }
}
