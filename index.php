<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="uv/uv.css">
    <title>RBGE Herbarium IIIF Endpoint</title>
</head>
<body>
    <h1>RBGE Herbarium IIIF Endpoint</h1>

    <p>
        Unfortunately we have had to move our IIIF server to a whitelist only access.
        It ran for five years totally open to anyone but over the last year has been brought down by excessive calls for data.
        From the logging data we identify three main causes of this:
    </p>

    <ol>
        <li>The "AI" investment bubble has provided massive resources for companies to hoover up as much data as possible to train their models.
            They will indescriminately call every URL they come across in other data sets.
            Ironically this approach isn't very "Intelligent" as the open data can usually be downloaded by more efficient means.
        </li>
        <li>An increasing number of researchers are writing their own code (possibly using "AI") to scrape data from the internet for analysis or just to archive.
            This has always happened but tools and laptops are fast enough now to create denial of service attacks on other academic institutions.
            We can see this happening in the logs when we get tens of requests a second from an academic IP address for ours on end.
            Training needs to be increased to prevent this.
        </li>
        <li>
            Many of the calls are impossible to pin down coming from a wide range of IP addresses with a variety of user agent strings and we suspect these are mallicious attacks
            aimed at institutions within a country by foreign actors. They may be 
        </li>
    </ol>
    
    <p>
        We have spent many hours trying to keep this service freely available by selectively filtering out bots and irresponsible users but for the time being all we can do is block access to those not on our whitelist of IP addresses.
    </p>

    <p>
        <strong>These images are still available for free. It is only this service that is restricted. Here is how to access them:</strong>
    </p>

    <ul>
        <li>Browse and download via our <a href="https://data.rbge.org.uk/search/herbarium/">Herbarium Catalogue</a>.</li>
        <li>View <a href="https://www.gbif.org/dataset/bf2a4bf0-5f31-11de-b67e-b8a03c50a862">our dataset at GBIF</a>.</li>
        <li>View <a href="https://www.europeana.eu/en/collections/organisation/2295-royal-botanic-garden-edinburgh">our dataset at Europeana</a>.</li>
        <li>If you would like to embed these images in your website then contact <a href="mailto:rhyam@rbge.org.uk?subject=IIIF">Roger Hyam</a> and we will try and facilitate it.</li>
        <li>If you would like data in bulk for analysis contact <a href="mailto:rcubey@rbge.org.uk?subject=IIIF">Rob Cubey</a> and we will try and facilitate it.</li>
    </ul>

	
</body>
</html>