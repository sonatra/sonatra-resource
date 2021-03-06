<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Resource\Handler;

use Fxp\Component\DoctrineExtra\Util\ClassUtils;
use Fxp\Component\Resource\Domain\DomainInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A form config list for domain.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class DomainFormConfigList extends FormConfigList
{
    /**
     * @var DomainInterface
     */
    protected $domain;

    /**
     * @var string
     */
    protected $identifier = 'id';

    /**
     * @var array
     */
    protected $defaultValueOptions = [];

    /**
     * @var bool
     */
    protected $creation = true;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * Constructor.
     *
     * @param DomainInterface $domain    The domain resource
     * @param string          $type      The class name of form type
     * @param array           $options   The form options for create the form type
     * @param string          $method    The request method
     * @param string          $converter The data converter for request content
     */
    public function __construct(
        DomainInterface $domain,
        string $type,
        array $options = [],
        string $method = Request::METHOD_POST,
        string $converter = 'json'
    ) {
        parent::__construct($type, $options, $method, $converter);

        $this->domain = $domain;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Set the config of identifier.
     *
     * @param string $identifier The property name of the identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * Set the default value options.
     *
     * @param array $options
     *
     * @return static
     */
    public function setDefaultValueOptions(array $options): self
    {
        $this->defaultValueOptions = $options;

        return $this;
    }

    /**
     * Set the creation.
     *
     * @param bool $isCreation
     *
     * @return static
     */
    public function setCreation(bool $isCreation): self
    {
        $this->creation = $isCreation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($object = null): array
    {
        $options = $this->options;

        if (\is_object($object)) {
            $class = ClassUtils::getClass($object);
            $metaFactory = $this->domain->getObjectManager()->getMetadataFactory();

            if ($metaFactory->hasMetadataFor($class)) {
                $validationGroups = array_merge($options['validation_groups'] ?? [], ['Default']);
                $validationGroups[] = empty($metaFactory->getMetadataFor($class)->getIdentifierValues($object))
                    ? 'Create'
                    : 'Update';
                $options['validation_groups'] = array_unique($validationGroups);
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function convertObjects(array &$list): array
    {
        if ($this->creation) {
            $size = \count($list);
            $objects = [];

            for ($i = 0; $i < $size; ++$i) {
                $objects[] = $this->domain->newInstance($this->defaultValueOptions);
            }
        } else {
            $ids = [];

            foreach ($list as &$record) {
                $ids[] = $record[$this->identifier] ?? 0;
                unset($record[$this->identifier]);
            }

            $objects = $this->findObjects($ids);
        }

        return $objects;
    }

    /**
     * Find the objects.
     *
     * @param int[] $ids The record ids
     *
     * @return array
     */
    protected function findObjects(array $ids): array
    {
        $foundObjects = $this->domain->getRepository()->findBy([
            $this->identifier => array_unique($ids),
        ]);
        $mapFinds = [];
        $objects = [];

        foreach ($foundObjects as $foundObject) {
            $id = $this->propertyAccessor->getValue($foundObject, $this->identifier);
            $mapFinds[$id] = $foundObject;
        }

        foreach ($ids as $i => $id) {
            $objects[$i] = $mapFinds[$id]
                ?? $this->domain->newInstance($this->defaultValueOptions);
        }

        return $objects;
    }
}
