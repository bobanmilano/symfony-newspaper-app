<?php

namespace App\Form;

use App\Entity\ArticleVideo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleVideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'Video URL (YouTube/Vimeo)',
                'attr' => ['placeholder' => 'https://www.youtube.com/watch?v=...'],
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
            'data_class' => ArticleVideo::class,
        ]);
    }
}
