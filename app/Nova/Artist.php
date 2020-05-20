<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Artist extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Artist::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static function group() {
        return __('nova.wiki');
    }

    public static function label()
    {
        return __('nova.artists');
    }

    public static function singularLabel()
    {
        return __('nova.artist');
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('nova.id'), 'artist_id')
                ->sortable(),

            Text::make(__('nova.name'), 'name')
                ->sortable()
                ->rules('required', 'max:192')
                ->help(__('nova.artist_name_help')),

            Text::make(__('nova.alias'), 'alias')
                ->sortable()
                ->rules('required', 'max:192', 'alpha_dash')
                ->creationRules('unique:artist,alias')
                ->updateRules('unique:artist,alias,{{resourceId}},artist_id')
                ->help(__('nova.artist_alias_help')),

            BelongsToMany::make(__('nova.songs'), 'Songs')
                ->searchable()
                ->withSubtitles(), //TODO: doesn't work, open issue

            BelongsToMany::make(__('nova.external_resources'), 'ExternalResources')
                ->searchable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [
            new Lenses\ArtistAniDbResourceLens,
            new Lenses\ArtistAnilistResourceLens,
            new Lenses\ArtistAnnResourceLens,
            new Lenses\ArtistMalResourceLens
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
