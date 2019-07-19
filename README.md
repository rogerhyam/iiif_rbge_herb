# iiif_rbge_herb
Implementation of IIIF v3 API for zoomified image tiles of herbarium specimens at the Royal Botanic Garden Edinburgh


## Cache

"Full" images are created for each layer of the zoomify tile pyramid on request by stitching the tiles together. This is inefficient and so the results are cached in a directory called 'cache'. The code makes no attempt to manage the size of this cache so a cron job will need to be added to clear it out from time to time.

