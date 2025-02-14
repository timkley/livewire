<?php

namespace Livewire\Features\SupportQueryString;

use Livewire\Livewire;
use Livewire\Component;
use Livewire\Attributes\Url;

class BrowserTest extends \Tests\BrowserTestCase
{
    /** @test */
    public function it_does_not_add_null_values_to_the_query_string_array()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                #[Url]
                public array $tableFilters = [
                    'filter_1' => [
                        'value' => null,
                    ],
                    'filter_2' => [
                        'value' => null,
                    ],
                    'filter_3' => [
                        'value' => null,
                    ]
                ];

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter_1" />

                    <input wire:model.live="tableFilters.filter_2.value" type="text" dusk="filter_2" />

                    <input wire:model.live="tableFilters.filter_3.value" type="text" dusk="filter_3" />
                </div>
                HTML; }
            },
        ])
        ->assertInputValue('@filter_1', '')
        ->assertInputValue('@filter_2', '')
        ->assertInputValue('@filter_3', '')
        ->assertQueryStringMissing('tableFilters')
        ->type('@filter_1', 'test')
        ->waitForLivewire()
        ->assertScript(
            '(new URLSearchParams(window.location.search)).toString()',
            'tableFilters%5Bfilter_1%5D%5Bvalue%5D=test'
        )
        ->refresh()
        ->assertInputValue('@filter_1', 'test')
        ;
    }

    /** @test */
    public function can_encode_url_containing_spaces_and_commas()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public $space = '';

                #[BaseUrl]
                public $comma = '';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="space" wire:model.live="space" />
                        <input type="text" dusk="comma" wire:model.live="comma" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForLivewire()
            ->type('@space', 'foo bar')
            ->type('@comma', 'foo,bar')
            ->assertScript('return !! window.location.search.match(/space=foo\+bar/)')
            ->assertScript('return !! window.location.search.match(/comma=foo\,bar/)');
    }

    /** @test */
    public function can_encode_url_containing_reserved_characters()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public $exclamation = '';

                #[BaseUrl]
                public $quote = '';

                #[BaseUrl]
                public $parentheses = '';

                #[BaseUrl]
                public $asterisk = '';

                public function render()
                {
                    return <<<'HTML'
                     <div>
                         <input type="text" dusk="exclamation" wire:model.live="exclamation" />
                         <input type="text" dusk="quote" wire:model.live="quote" />
                         <input type="text" dusk="parentheses" wire:model.live="parentheses" />
                         <input type="text" dusk="asterisk" wire:model.live="asterisk" />
                     </div>
                     HTML;
                }
            },
        ])
            ->waitForLivewire()
            ->type('@exclamation', 'foo!')
            ->type('@parentheses', 'foo(bar)')
            ->type('@asterisk', 'foo*')
            ->assertScript('return !! window.location.search.match(/exclamation=foo\!/)')
            ->assertScript('return !! window.location.search.match(/parentheses=foo\(bar\)/)')
            ->assertScript('return !! window.location.search.match(/asterisk=foo\*/)')
        ;
    }

    /** @test */
    public function can_use_a_value_other_than_initial_for_except_behavior()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl(except: '')]
                public $search = '';

                public function mount()
                {
                    $this->search = 'foo';
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="search" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringHas('search', 'foo')
            ->waitForLivewire()->type('@input', 'bar')
            ->assertQueryStringHas('search', 'bar')
            ->waitForLivewire()->type('@input', ' ')
            ->waitForLivewire()->keys('@input', '{backspace}')
            ->assertQueryStringMissing('search')
        ;
    }

    /** @test */
    public function can_use_except_and_it_still_works_after_a_page_refresh()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl(except: '')]
                public $search = '';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="search" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('search')
            ->waitForLivewire()->type('@input', 'bar')
            ->assertQueryStringHas('search', 'bar')
            ->refresh()
            ->waitForLivewire()->type('@input', ' ')
            ->waitForLivewire()->keys('@input', '{backspace}')
            ->assertQueryStringMissing('search')
        ;
    }

    /** @test */
    public function can_use_except_in_query_string_property()
    {
        Livewire::visit([
            new class extends Component
            {
                protected $queryString = [
                    'search' => [
                        'except' => '',
                        'history' => false,
                    ],
                ];

                public $search = '';

                public function mount()
                {
                    $this->search = 'foo';
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="search" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringHas('search', 'foo')
            ->waitForLivewire()->type('@input', 'bar')
            ->assertQueryStringHas('search', 'bar')
            ->waitForLivewire()->type('@input', ' ')
            ->waitForLivewire()->keys('@input', '{backspace}')
            ->assertQueryStringMissing('search')
        ;
    }

    /** @test */
    public function can_use_url_on_form_object_properties()
    {
        Livewire::visit([
            new class extends Component
            {
                public FormObject $form;

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="foo.input" wire:model.live="form.foo" />
                        <input type="text" dusk="bob.input" wire:model.live="form.bob" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringMissing('aliased')
            ->waitForLivewire()->type('@foo.input', 'baz')
            ->assertQueryStringHas('foo', 'baz')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringMissing('aliased')
            ->waitForLivewire()->type('@bob.input', 'law')
            ->assertQueryStringHas('foo', 'baz')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringHas('aliased', 'law')
        ;
    }

    /** @test */
    public function can_use_url_on_enum_object_properties()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public EnumForUrlTesting $foo = EnumForUrlTesting::First;

                public function change()
                {
                    $this->foo = EnumForUrlTesting::Second;
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="change" dusk="button">Change</button>
                        <h1 dusk="output">{{ $foo }}</h1>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->assertSeeIn('@output', 'first')
            ->waitForLivewire()->click('@button')
            ->assertQueryStringHas('foo', 'second')
            ->assertSeeIn('@output', 'second')
            ->refresh()
            ->assertQueryStringHas('foo', 'second')
            ->assertSeeIn('@output', 'second')
        ;
    }

    /** @test */
    public function it_does_not_break_string_typed_properties()
    {
        Livewire::withQueryParams(['foo' => 'bar'])
            ->visit([
                new class extends Component
                {
                    #[BaseUrl]
                    public string $foo = '';

                    public function render()
                    {
                        return <<<'HTML'
                        <div>
                            <h1 dusk="output">{{ $foo }}</h1>
                        </div>
                        HTML;
                    }
                },
            ])
            ->assertSeeIn('@output', 'bar')
        ;
    }

    /** @test */
    public function can_use_url_on_lazy_component()
    {
        Livewire::visit([
            new class extends Component
            {
                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <livewire:child lazy />
                    </div>
                    HTML;
                }
            },
            'child' => new class extends Component
            {
                #[BaseUrl]
                public $foo = 'bar';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <div>lazy loaded</div>
                        <input type="text" dusk="foo.input" wire:model.live="foo" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForText('lazy loaded')
            ->assertQueryStringMissing('foo')
            ->waitForLivewire()->type('@foo.input', 'baz')
            ->assertQueryStringHas('foo', 'baz')
        ;
    }
}

class FormObject extends \Livewire\Form
{
    #[\Livewire\Attributes\Url]
    public $foo = 'bar';

    #[\Livewire\Attributes\Url(as: 'aliased')]
    public $bob = 'lob';
}

enum EnumForUrlTesting: string
{
    case First = 'first';
    case Second = 'second';
}
