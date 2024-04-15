<?php

namespace Lyre\Traits;

use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;

trait HasCustomTrait
{
    public static function getCreateAction()
    {
        return Tables\Actions\CreateAction::make()
            ->using(function (array $data, string $model, Table $table): Model {
                $relationship = $table->getRelationship();

                $pivotData = [];

                if ($relationship instanceof BelongsToMany) {
                    $pivotColumns = $relationship->getPivotColumns();

                    $pivotData = Arr::only($data, $pivotColumns);
                    if (isset($data['owner_type'])) {
                        $pivotData = [ ...$pivotData, 'owner_type' => $data['owner_type']];
                    }
                    $data = Arr::except($data, $pivotColumns);
                }

                if ($translatableContentDriver = $table->makeTranslatableContentDriver()) {
                    $record = $translatableContentDriver->makeRecord($model, $data);
                } else {
                    $record = new $model();
                    $record->fill($data);
                }

                if (
                    (!$relationship) ||
                    $relationship instanceof HasManyThrough
                ) {
                    $record->save();

                    return $record;
                }

                if ($relationship instanceof BelongsToMany) {
                    $relationship->save($record, $pivotData);

                    return $record;
                }

                /** @phpstan-ignore-next-line */
                $relationship->save($record);

                return $record;
            });
    }
}
