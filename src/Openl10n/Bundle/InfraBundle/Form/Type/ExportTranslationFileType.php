<?php

namespace Openl10n\Bundle\InfraBundle\Form\Type;

use Openl10n\Domain\Project\Model\Language;
use Openl10n\Domain\Project\Repository\LanguageRepository;
use Openl10n\Domain\Resource\Application\Action\ExportTranslationFileAction;
use Openl10n\Domain\Resource\Service\Dumper\TranslationDumperInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ExportTranslationFileType extends AbstractType
{
    protected $languageRepository;
    protected $translationDumper;

    public function __construct(LanguageRepository $languageRepository, TranslationDumperInterface $translationDumper)
    {
        $this->languageRepository = $languageRepository;
        $this->translationDumper = $translationDumper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                if (null === $data) {
                    return;
                }

                $project = $data->getResource()->getProject();
                $languages = $this->languageRepository->findByProject($project);

                $locales = array_map(function(Language $language) {
                    return (string) $language->getLocale();
                }, $languages);

                $form
                    ->add('locale', 'openl10n_locale_choice', array(
                        'restrict' => $locales,
                    ))
                ;
            })
            ->add('format', 'choice', array(
                'choices' => $this->getFormats()
            ))
            ->add('options', 'choice', array(
                'choices' => array(
                    ExportTranslationFileAction::OPTION_REVIEWED => 'Only export reviewed translations',
                    ExportTranslationFileAction::OPTION_FALLBACK_LOCALE => 'Fallback on default locale',
                    ExportTranslationFileAction::OPTION_FALLBACK_KEY => 'Use key as fallback',
                ),
                'multiple' => true,
                'expanded' => true,
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'openl10n_export_translation_file';
    }

    protected function getFormats()
    {
        $formats = $this->translationDumper->getFormats();
        $values = array_map(function($format) {
            return '.'.$format;
        }, $formats);

        return array_combine($formats, $values);
    }
}
