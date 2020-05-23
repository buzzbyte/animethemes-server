<?php

use App\Enums\ThemeType;
use App\Models\Anime;
use App\Models\Entry;
use App\Models\Song;
use App\Models\Theme;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AnimeThemeSeeder extends Seeder
{

    // Hard-coded addresses of year pages
    // I don't really care about making this more elegant
    const YEAR_PAGES = [
        'https://www.reddit.com/r/AnimeThemes/wiki/60s.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/70s.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/80s.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/90s.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2000.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2001.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2002.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2003.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2004.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2005.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2006.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2007.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2008.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2009.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2010.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2011.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2012.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2013.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2014.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2015.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2016.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2017.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2018.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2019.json',
        'https://www.reddit.com/r/AnimeThemes/wiki/2020.json',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (AnimeThemeSeeder::YEAR_PAGES as $year_page) {

            // Try not to upset Reddit
            sleep(rand(5, 15));

            // Get JSON of Year page content
            $year_wiki_contents = file_get_contents($year_page);
            $year_wiki_json = json_decode($year_wiki_contents);
            $year_wiki_content_md = $year_wiki_json->data->content_md;

            // We want to proceed line by line
            preg_match_all('/^(.*)$/m', $year_wiki_content_md, $anime_theme_wiki_entries, PREG_SET_ORDER);

            // The current Anime & Group
            $anime = NULL;
            $group = NULL;
            $theme = NULL;
            $entry = NULL;

            foreach ($anime_theme_wiki_entries as $anime_theme_wiki_entry) {
                $wiki_entry_line = html_entity_decode($anime_theme_wiki_entry[0]);

                // If Anime heading line, attempt to set current Anime
                if (preg_match('/^###\[(.*)\]\(https\:\/\/.*\)(?:\\r)?$/', $wiki_entry_line, $anime_name)) {
                    try {
                        // Set current Anime if we have a definitive match
                        // This is not guaranteed as an Anime Name may be inconsistent between indices
                        $matching_anime = Anime::where('name', html_entity_decode($anime_name[1]));
                        if ($matching_anime->count() === 1) {
                            $anime = $matching_anime->first();
                            $group = NULL;
                            $theme = NULL;
                            $entry = NULL;
                            continue;
                        }
                    } catch (Exception $exception) {
                        LOG::error($exception);
                    }

                    $anime = NULL;
                    $group = NULL;
                    $theme = NULL;
                    $entry = NULL;
                    continue;
                }

                // If Synonym heading line, attempt to set Synonyms for Anime
                if (!is_null($anime) && preg_match('/^\*\*(.*)\*\*(?:\\r)?$/', $wiki_entry_line, $synonyms)) {
                    $synonym_list = explode(', ', html_entity_decode($synonyms[1]));
                    foreach ($synonym_list as $synonym) {
                        $anime->synonyms()->create([
                            'text' => $synonym
                        ]);
                    }
                    continue;
                }

                // If group line, attempt to set current group
                if (!is_null($anime) && preg_match('/^([\w\s]+)(?:\\r)?$/', $wiki_entry_line, $group_name)) {
                    $group = Str::of(html_entity_decode($group_name[1]))->trim();
                    if (empty($group)) {
                        $group = NULL;
                    } else {
                        $theme = NULL;
                        $entry = NULL;
                    }
                    continue;
                }

                // If Theme line, attempt to create Theme/Entry
                // Needs to handle missing video
                if (!is_null($anime) && preg_match('/^(OP|ED)(\d*)(?:\sV(\d))?.*\"(.*)\"(?:\sby\s(.*))?\|\[Webm.*\]\(https\:\/\/animethemes\.moe\/video\/(.*)\)\|(.*)\|(.*)(?:\\r)?$/', $wiki_entry_line, $theme_match)) {
                    $theme_type = $theme_match[1];
                    $sequence = $theme_match[2];
                    $version = $theme_match[3];
                    $song_title = html_entity_decode($theme_match[4]);
                    $song_by = html_entity_decode($theme_match[5]);
                    $video_basename = $theme_match[6];
                    $episodes = $theme_match[7];
                    $notes = Str::of(html_entity_decode($theme_match[8]))->trim();

                    // Create Theme if No version or V1
                    if (!is_numeric($version) || intval($version) === 1) {
                        // Create Song
                        $song = Song::create([
                            'title' => $song_title,
                            'by' => $song_by
                        ]);

                        // Create Theme
                        $theme = new Theme;
                        $theme->group = $group;
                        $theme->type = ThemeType::getValue(strtoupper($theme_type));
                        if (is_numeric($sequence)) {
                            $theme->sequence = intval($sequence);
                        }

                        // Associate Song & Anime to Theme
                        $theme->anime()->associate($anime);
                        $theme->song()->associate($song);
                        $theme->save();

                        $entry = self::create_entry($version, $episodes, $notes, $theme);

                        self::attach_video_to_entry($video_basename, $entry);
                    }

                    // Create Entry of Current Theme if V2+
                    if (!is_null($theme) && is_numeric($version) && intval($version) > 1) {
                        $entry = self::create_entry($version, $episodes, $notes, $theme);
                        self::attach_video_to_entry($video_basename, $entry);
                    }

                    continue;
                }

                // If Entry Video line, attempt to create Entry Video
                if (!is_null($entry) && preg_match('/^\|\|\[Webm.*\]\(https\:\/\/animethemes\.moe\/video\/(.*)\)\|\|(?:\\r)?$/', $wiki_entry_line, $video_name)) {
                    $video_basename = $video_name[1];
                    self::attach_video_to_entry($video_basename, $entry);
                }
            }
        }
    }

    private static function create_entry($version, $episodes, $notes, $theme) {
        $entry = new Entry;

        if (is_numeric($version)) {
            $entry->version = intval($version);
        }
        $entry->episodes = $episodes;
        if (Str::contains(strtoupper($notes), 'NSFW')) {
            $entry->nsfw = True;
        }
        if (Str::contains(strtoupper($notes), 'SPOILER')) {
            $entry->spoiler = True;
        }
        $entry->notes = preg_replace('/^(?:NSFW)?(?:,\s)?(?:Spoiler)?$/', '', $notes);

        $entry->theme()->associate($theme);
        $entry->save();

        return $entry;
    }

    private static function attach_video_to_entry($video_basename, $entry): void {
        try {
            $video = Video::where('basename', $video_basename)->firstOrFail();
            $entry->videos()->attach($video);
        } catch (Exception $exception) {
            LOG::error($exception);
        }
    }
}