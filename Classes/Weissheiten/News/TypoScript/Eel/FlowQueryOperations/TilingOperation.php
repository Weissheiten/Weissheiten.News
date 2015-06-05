<?php
namespace Weissheiten\News\TypoScript\Eel\FlowQueryOperations;

/*                                                                              *
 * This script belongs to the TYPO3 Flow package "Weissheiten.News".            *
 *                                                                              */

use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\Media\Domain\Model\Tag;
/**
 * EEL tiling operation to setup everything for displaying news as tiles
 */
class TilingOperation extends AbstractOperation {
    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Repository\TagRepository
     */
    protected $tagRepository;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    static protected $shortName = 'tile';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    static protected $priority = 50;

    /**
     * {@inheritdoc}
     *
     * We can only handle TYPO3CR Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context) {
        return (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return mixed
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments) {
        //if (!isset($arguments[0]) || empty($arguments[0])) {
        //    throw new \TYPO3\Eel\FlowQuery\FlowQueryException('tile() needs property amount of columns available for which the news should be distributed', 1332492263);
        //} else {
            $nodes = $flowQuery->getContext();

            // number of Columns that are available for filling, the last tile is always the "more news" button
            //$colsAvailable = $arguments[0] - 1;

            // Tag for flavor images
            $tag = $this->tagRepository->findBySearchTerm('FlavorTiles')->getFirst();

            // get all available flavor images
            $flavorImages = $this->assetRepository->findByTag($tag)->toArray();
            shuffle($flavorImages);
            foreach ($nodes as $node) {
                // Calculate the number of cols reserved
                $reservedCols = $this->calcColNumber($node);
                // substract from available Cols
                //$colsAvailable -= $reservedCols;
                // add the info on how many cols should be rendered to the node
                //$node->setProperty('newsCols', $reservedCols);

                $tiledNodes[] = $node;

                //\TYPO3\Flow\var_dump($flavorImages);
                // if there is only one tile reserved we also add a flavor image
                if($reservedCols==1){
                    //$tiledNodes[] = array_pop($flavorImages);
                    $node->setProperty('previewThumb', array_pop($flavorImages));
                    // start over from the first image if the last image was used
                    if(count($flavorImages)<1){
                        $flavorImages = $this->assetRepository->findByTag($tag)->toArray();
                        shuffle($flavorImages);
                    }
                }
            }

            // the last tile is always a single one so we add an additional image to the end of the nodearray
            //$tiledNodes[] = array_pop($flavorImages);

            $flowQuery->setContext($tiledNodes);
        //}
    }

    /**
     * {@inheritdoc}
     *
     * Calculates the number of columns reserved for this news entry
     * Evaluation works as following:
     * Important News => 2 Tiles
     * News with a preview image => 2 tile
     * Else render as 1 Tile
     *
     * @param NodeInterface $node
     * @return integer
     */
    private function calcColNumber($node){
        // define how many colums are reserved for each entry
        // Important news always get 2 columns
        if($node->getProperty('important')===true){
            return 2;
        }
        // if the news entry has a preview thumb it receives 2 tiles
        if($node->getProperty('previewThumb')!=null) {
            return 2;
        }
        // else reserve 1 column
        return 1;
    }
}