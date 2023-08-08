<?php

namespace Johnkhansrc\ApiPlatformStreamTranslateBundle\EventListener;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use ReflectionClass;
use Johnkhansrc\ApiPlatformStreamTranslateBundle\Annotation\StreamTranslate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class StreamTranslateAnnotationListener implements EventSubscriberInterface
{
    private Reader $annotationReader;
    private TranslatorInterface $translator;

    public function __construct(Reader $annotationReader, TranslatorInterface $translator)
    {
        $this->annotationReader = $annotationReader;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['translateOnResponse', EventPriorities::PRE_SERIALIZE]
            ],
        ];
    }

    /**
     * @throws \ReflectionException|\Exception
     */
    public function translateOnResponse(ViewEvent $event): void
    {
        $translatable = $event->getControllerResult();
        if ($translatable instanceof Paginator || is_array($translatable) || $translatable instanceof Collection) {
            foreach ((is_array($translatable)) ? $translatable : $translatable->getIterator() as $item) {
                $this->translateRessource($item);
            }
            return;
        }
        $this->translateRessource($translatable);
    }

    /**
     * @param mixed $ressource
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function translateRessource($ressource): void
    {
        if (!$ressource) {
            return;
        }
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $reflector = new ReflectionClass($ressource);
        foreach ($reflector->getProperties() as $property) {
            if (!$propertyAccessor->isReadable($ressource, $property->name)) {
                continue;
            }

            $annotation = $this->annotationReader->getPropertyAnnotation($property, StreamTranslate::class);
            if (!$annotation) {
                continue;
            }

            if ($annotation->childs) {
                $this->translateChildsProperties($propertyAccessor->getValue($ressource, $property->name));
            } else {

                $this->translateProperty($ressource, $property->name, $annotation);
            }
        }
    }

    /**
     * @param array|Collection $ressources
     * @return void
     * @throws \ReflectionException
     */
    public function translateChildsProperties($ressources): void
    {
        if (!$ressources) {
            return;
        }
        if (is_iterable($ressources)) {
            foreach ($ressources as $ressource) {
                $this->translateRessource($ressource);
            }
        } else {
            $this->translateRessource($ressources);
        }
    }

    /**
     * @param mixed $ressource
     * @throws \Exception
     */
    private function translateProperty($ressource, string $propertyName, StreamTranslate $annotation): void
    {
        if (!$ressource) {
            return;
        }
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue(
            $ressource,
            $propertyName,
            $this->translator->trans(
                $annotation->key ?? $propertyAccessor->getValue($ressource, $propertyName),
                [],
                $annotation->domain
            )
        );
    }
}
