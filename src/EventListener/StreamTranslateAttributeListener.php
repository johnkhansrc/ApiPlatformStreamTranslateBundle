<?php

namespace Johnkhansrc\ApiPlatformStreamTranslateBundle\EventListener;


use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Proxy;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use Johnkhansrc\ApiPlatformStreamTranslateBundle\Attribute\StreamTranslate;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class StreamTranslateAttributeListener implements EventSubscriberInterface
{
    public function __construct(private TranslatorInterface $translator)
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['translateOnResponse', EventPriorities::PRE_SERIALIZE]
            ],
        ];
    }

    /**
     * @throws ReflectionException|Exception
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
     * @throws ReflectionException
     * @throws Exception
     */
    private function translateRessource(mixed $ressource): void
    {
        if (!$ressource) {
            return;
        }
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $reflector = new ReflectionClass($ressource);
        if ($ressource instanceof Proxy) {
            $reflector = $reflector->getParentClass();
            if (!$reflector) {
                return;
            }
        }
        foreach ($reflector->getProperties() as $property) {
            $property->setAccessible(true);
            if (!$propertyAccessor->isReadable($ressource, $property->name)) {
                continue;
            }

            $attribute = $property->getAttributes(StreamTranslate::class, ReflectionAttribute::IS_INSTANCEOF);
            if (!$attribute) {
                continue;
            }

            $reflexionAttribute = $attribute[0];

            /** @var StreamTranslate $attribute */
            $attribute = $reflexionAttribute->newInstance();

            if ($attribute->childs) {
                $this->translateChildsProperties($propertyAccessor->getValue($ressource, $property->name));
            } else {

                $this->translateProperty($ressource, $property->name, $attribute);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    public function translateChildsProperties(mixed $ressources): void
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
     * @throws Exception
     */
    private function translateProperty(mixed $ressource, string $propertyName, StreamTranslate $attribute): void
    {
        if (!$ressource) {
            return;
        }
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue(
            $ressource,
            $propertyName,
            $this->translator->trans(
                $attribute->key ?? $propertyAccessor->getValue($ressource, $propertyName),
                [],
                $attribute->domain
            )
        );
    }
}
