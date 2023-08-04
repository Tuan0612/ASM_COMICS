<?php

namespace App\Form;

use App\Entity\Chapter;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ChapterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
            ])
            ->add('chapterImage', FileType::class, [
                'label' => 'Chapter Images',
                'mapped' => false,
                'required' => false,
                'multiple' => true, // Chỉ định cho phép chọn nhiều file
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('date', DateType::class, ['widget' => 'single_text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chapter::class,
        ]);
    }
}
