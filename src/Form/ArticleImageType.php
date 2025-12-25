<?php

namespace App\Form;

use App\Entity\ArticleImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ArticleImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Remove image',
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
                'label' => 'Image Upload',
            ])
            ->add('caption', TextType::class, [
                'required' => false,
                'label' => 'Caption (optional)',
            ])
            ->add('position', IntegerType::class, [
                'required' => false,
                'label' => 'Sort Order',
                'attr' => ['min' => 0],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleImage::class,
        ]);
    }
}
