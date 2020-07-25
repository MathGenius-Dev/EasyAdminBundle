<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ChoiceConfiguratorTest extends KernelTestCase
{
    private $choices;
    private $configurator;
    private $entityDto;
    private $adminContext;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->choices = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->configurator = new ChoiceConfigurator(self::$container->get('translator'));
        $this->entityDto = $this->createMock(EntityDto::class);

        $crudMock = $this->getMockBuilder(CrudDto::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentPage'])
            ->getMock();
        $crudMock->method('getCurrentPage')->willReturn(Crud::PAGE_INDEX);

        $i18nMock = $this->getMockBuilder(I18nDto::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTranslationParameters', 'getTranslationDomain'])
            ->getMock();
        $i18nMock->method('getTranslationParameters')->willReturn([]);
        $i18nMock->method('getTranslationDomain')->willReturn('messages');

        $adminContextMock = $this->getMockBuilder(AdminContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCrud', 'getI18n'])
            ->getMock();
        $adminContextMock
            ->expects($this->any())
            ->method('getCrud')
            ->willReturn($crudMock);
        $adminContextMock
            ->expects($this->any())
            ->method('getI18n')
            ->willReturn($i18nMock);

        $this->adminContext = $adminContextMock;
    }

    public function testFieldWithoutChoices()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = ChoiceField::new('foo');
        $this->configure($field);
    }

    public function testFieldWithWrongVisualOptions()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = ChoiceField::new('foo')->setChoices($this->choices);
        $field->renderExpanded();
        $field->renderAsNativeWidget(false);
        $this->configure($field);
    }

    public function testDefaultWidget()
    {
        $field = ChoiceField::new('foo')->setChoices($this->choices);

        $field->renderExpanded(false);
        $field->setCustomOption(ChoiceField::OPTION_WIDGET, null);
        self::assertSame(ChoiceField::WIDGET_AUTOCOMPLETE, $this->configure($field)->getCustomOption(ChoiceField::OPTION_WIDGET));

        $field->renderExpanded(true);
        $field->setCustomOption(ChoiceField::OPTION_WIDGET, null);
        $fieldDto = $this->configure($field);
        self::assertSame(ChoiceField::WIDGET_NATIVE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
        self::assertSame('select2', $fieldDto->getFormTypeOption('attr.data-widget'));
    }

    public function testFieldFormOptions()
    {
        $field = ChoiceField::new('foo')->setChoices($this->choices);
        $field->renderExpanded();
        $field->allowMultipleChoices();

        self::assertSame(
            ['choices' => $this->choices, 'multiple' => true, 'expanded' => true],
            $this->configure($field)->getFormTypeOptions()
        );
    }

    public function testBadges()
    {
        $field = ChoiceField::new('foo')->setChoices($this->choices);

        $field->setValue(1);
        self::assertSame('a', $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3]);
        self::assertSame('a, c', $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges();
        self::assertSame('<span class="badge badge-pill badge-secondary">a</span>', $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges();
        self::assertSame('<span class="badge badge-pill badge-secondary">a</span><span class="badge badge-pill badge-secondary">c</span>', $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges([1 => 'warning', '3' => 'danger']);
        self::assertSame('<span class="badge badge-pill badge-warning">a</span>', $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges([1 => 'warning', '3' => 'danger']);
        self::assertSame('<span class="badge badge-pill badge-warning">a</span><span class="badge badge-pill badge-danger">c</span>', $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges(function ($value) { return $value > 1 ? 'success' : 'primary'; });
        self::assertSame('<span class="badge badge-pill badge-primary">a</span>', $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges(function ($value) { return $value > 1 ? 'success' : 'primary'; });
        self::assertSame('<span class="badge badge-pill badge-primary">a</span><span class="badge badge-pill badge-success">c</span>', $this->configure($field)->getFormattedValue());
    }

    private function configure(FieldInterface $field): FieldDto
    {
        $fieldDto = $field->getAsDto();
        $this->configurator->configure($fieldDto, $this->entityDto, $this->adminContext);

        return $fieldDto;
    }
}
