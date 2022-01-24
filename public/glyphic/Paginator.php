<?php

declare(strict_types=1);
namespace glyphic;

class Paginator
{

    public static function paginate(
        array $results,
        int $itemsPerPage,
        int $pageQuery
        )
    {

        $itemsPerPageIterator = 1;
        $pageQueryIncrementor = 1;
        $truncatedResult = [];

        foreach ($results as $key => $result) {

            # Points to the current result key in the loop
            $currentResultAt = $key + 1;

            # Checks if the desired number of items for result has been achieved
            if ( $itemsPerPageIterator > $itemsPerPage ) {

                # Sufficient number of items for result has been achieved,
                # iterator must start from the beginning again
                $itemsPerPageIterator = 2;

                # New sets of results must be treated as new page, hence
                # page number is incremented
                $pageQueryIncrementor++;

            } else {

                $itemsPerPageIterator++;

            }

            //echo $itemsPerPageIterator.'='.$pageQueryIncrementor.' ';

            # Checks to see if the desired page (pageQuery) has been reached
            # while looping over the result
            if ($pageQueryIncrementor===$pageQuery) {

                # Results must be saved and returned later after the loop
                array_push($truncatedResult,$result);

            }

        }

        return [
            'aggregated' => $truncatedResult,
            'totalPage' => $pageQueryIncrementor
        ];

    }


}
