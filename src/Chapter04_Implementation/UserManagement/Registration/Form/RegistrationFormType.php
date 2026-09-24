<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Registration\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // NotBlank tu není duplicita pravidla z commandu. Bez data_class
        // formulář constrainty commandu nepřebírá a prázdné pole přijde
        // do konstruktoru jako null – tedy 500 dřív, než se validace spustí.
        $builder
            ->add('name', TextType::class, [
                'label' => 'Jméno',
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new NotBlank()],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Heslo (aspoň 12 znaků)',
                'constraints' => [new NotBlank()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Bez data_class vrací formulář pole, a to je zde záměr: command
        // má promované readonly vlastnosti, do kterých PropertyAccess
        // zapsat neumí. Kontroler z pole postaví command sám.
        $resolver->setDefaults(['data_class' => null]);
    }
}
