<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'In Progress' => 'in_progress',
                    'Done' => 'done',
                    'Blocked' => 'blocked',
                ],
                'placeholder' => 'Any',
            ])
            ->add('filename', TextType::class, ['required' => false]);
    }
}
