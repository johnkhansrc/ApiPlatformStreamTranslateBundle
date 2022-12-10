# ApiPlatformStreamTranslateBundle
A lightweight extension allowing to translate in the ApiPlatform flow the properties values of an entity by a simple annotation.

## Requirements
- symfony/translation >=5.4
- api-platform/core: >=2.5
- doctrine/annotations >=1.0

## Instalation
```
composer require johnkhansrc/api-platform-stream-translate-bundle
```
Update your services.yaml
```yaml
Johnkhansrc\ApiPlatformStreamTranslateBundle\EventListener\StreamTranslateAnnotationListener: ~
```

## Usage
```php
use Johnkhansrc\ApiPlatformStreamTranslateBundle\Annotation\StreamTranslate;

/**
 * @ApiResource
 * @ORM\Entity(repositoryClass=AnyEntityRepository::class)
 */
class AnyEntity
{
    /**
     * @ORM\Id
     */
    private $id;
    
    /**
     * Expect translation file anyDomain.xx.yaml who contain 'anykey' key
     *
     * @StreamTranslate(domain="anyDomain", key="anyKey")
     */
    private string $anyStringPropertyKeyBasedExample;
    

    /**
     * Expect translation file anyDomain.xx.yaml who contain property value as key
     *
     * @StreamTranslate(domain="anyDomain")
     */
    private string $anyStringPropertyNoKeyBasedExample;
    

    /**
     * * * NEW ON 2.0.0 * * *
     * Iterate on each embed relation, don't forget do annotate related class properties.
     * Tips: You can use different domain on related class property's annotation.
     *
     * @StreamTranslate(domain="anyDomain", childs=true)
     */
    private ArrayCollection $anyStringPropertyNoKeyBasedExample;
}
```
