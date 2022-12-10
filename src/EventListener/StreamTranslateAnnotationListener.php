<?php

namespace Johnkhansrc\ApiPlatformStreamTranslateBundle\EventListener;


use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\EventListener\EventPriorities;
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
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::VIEW => [['translateOnResponse', EventPriorities::PRE_SERIALIZE]],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function translateOnResponse(ViewEvent $event): void
    {
        $translatable = $event->getControllerResult();
        if ($translatable instanceof Paginator || is_array($translatable) || $translatable instanceof Collection) {
            foreach ((is_array($translatable)) ? $translatable : $translatable->getIterator() as $item) {
                $this->translateProperties($item);
            }
            return;
        }
        $this->translateProperties($translatable);
    }

    private function translateProperties($item): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $reflector = new ReflectionClass($item);
        foreach ($reflector->getProperties() as $property) {
            $annotation = $this->annotationReader->getPropertyAnnotation($property, StreamTranslate::class);
            if ($annotation instanceof StreamTranslate) {
                $propertyAccessor->setValue(
                    $item,
                    $property->name,
                    $this->translator->trans(
                        $annotation->key ?? $propertyAccessor->getValue($item, $property->name),
                        [],
                        $annotation->domain
                    )
                );
            }
        }
    }
}
