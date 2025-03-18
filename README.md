# Retumador

Retumador is an CLI command which allow to create a RSS feed for (almost) any website.

## Usage

Create a `config.json` file which looks like this one:

```json
{
    "name": "Human Immobilier",
    "url": "https://www.human-immobilier.fr/achat-maison-terrain-marsac-le-grand-bourg-benevent-l-abbaye-saint-etienne-de-fursac?quartiers=&surface=&surfaceMax=&sterr=&sterrMax=&prix=-100000000&typebien=1-3&nbpieces=1-2-3-4-5&og=0&type=5&where=Marsac-__23210_$Le-Grand-Bourg-__23240_$Benevent-l-Abbaye-__23210_$Saint-%C3%89tienne-de-Fursac-__23290_&_b=1&_p=1&tyloc=5&travaux=1-2-4-5&neuf=1&ancien=1&ids=23124-23095-23021-23192",
    "browser": "firefox",
    "selectors": {
        "item": "//div[@class=\"bien bien-bi\"]",
        "title": ".//a/@title",
        "link": ".//a/@href"
    }
}
```

Then run the following command:

```bash
docker run -t -v ${PWD}:/tmp odolbeau/retumador:latest crawl /tmp/config.json -o /tmp/feed.rss.xml
```

Open `feed.rss.xml` to see your generated feed! 🥳
