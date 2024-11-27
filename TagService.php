<?php

class TagService
{
    use ReplaceModelNameTrait;

    public function getGlobalUsedTags(array $params): Collection
    {
        $tags = [];
        $searchIds = [];
        $type = !empty($params['type']) ? $params['type'] : null;
        $spaceId = !empty($params['space_id']) ? $params['space_id'] : null;
        $tagModels = [
            'posts' => PostTag::class,
            'stories' => StoryTag::class,
            'streams' => StreamTag::class,
            'audio_playlists' => AudioPlaylistTag::class,
            'events' => EventTag::class,
            'courses' => CourseTag::class,
            'giveaways' => GiveawayTag::class,
            'spaces' => SpaceTag::class,
        ];

        $models = [
            'posts' => Post::class,
            'stories' => Story::class,
            'streams' => Stream::class,
            'audio_playlists' => AudioPlaylist::class,
            'events' => Event::class,
            'courses' => Course::class,
            'giveaways' => Giveaway::class,
        ];

        if ($spaceId) {
            foreach ($models as $key => $model) {
                /** @var Model $model */

                if (!$type || $type === $key) {
                    $newSearchIds = $model::query()
                        ->where('space_id', $spaceId)
                        ->pluck('id');

                    $searchIds = array_unique([...$searchIds, ...$newSearchIds]);
                }
            }
        }

        foreach ($tagModels as $key => $model) {
            /** @var Model $model */

            if (!$type || $type === $key) {
                $newTags = $model::query()
                    ->when(!empty($searchIds) || $spaceId, function ($query) use ($searchIds, $key) {
                        $query->whereIn($this->manyToOne($key) . '_id', $searchIds);
                    })
                    ->distinct('tag_id')
                    ->pluck('tag_id')
                    ->toArray();

                $tags = array_unique([...$tags, ...$newTags]);
            }
        }

        return Tag::query()
            ->orderBy('name_' . user()->language)
            ->whereIn('id', $tags)
            ->get(['tags.id', 'tags.name_en', 'tags.name_ru', 'tags.name_es', 'tags.color']);
    }

    public function getSpaceUsedTags(): Collection
    {
        return DB::table('tags')
            ->rightJoin('space_tags', 'tags.id', '=', 'space_tags.tag_id')
            ->distinct('tags.id')
            ->select('tags.id', 'tags.name_en', 'tags.name_ru', 'tags.name_es', 'tags.color')
            ->orderBy('name_' . user()->language)
            ->get();
    }
}
