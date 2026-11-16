<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Catégorie parente')
                    ->relationship(
                        'parent',
                        'name',
                        fn (Builder $query, $record) => $query
                            ->whereNull('parent_id')
                            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey())),
                    )
                    ->placeholder('Aucune (catégorie principale)')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Identifiant (slug)')
                    ->helperText('Laissez vide : il sera généré à partir du nom.')
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }
}