# Bionomia to RDF

Convert Bionomia data export to triples. The latest version of the data is on Zenodo https://doi.org/10.5281/zenodo.13937805, and comprises two CSV files: bionomia-public-claims.csv, and bionomia-public-profiles.csv. Data files are in the data directory in folders labelled by the Zenodo record number (for example 21734026).

bionomia-public-claims.csv has three columns Subject, Predicate, and Object, so is close to [N-Triples](https://en.wikipedia.org/wiki/N-Triples). The GBIF occurrence URL is https://gbif.org which differs from the https://www.gbif.org form used by GBIF RDF.

bionomia-public-profiles.csv has columns Family, Given, Particle, 	OtherNames	, LabelName, Country, Keywords, wikidata, ORCID, URL which requires mapping to a vocabulary, I have used schema.org.

As a test the output of `claims.php` and `profiles.php` was uploaded to a koetai instance https://koetai.bionames.org/fdp/dataset/0000-0002-7101-9767/bionomia.

## Queries

This federated query needs to be run on QLever, e.g. the https://qlever.dev/api/gbif endpoint. There are issues with federated queries, especially using Oxigraph, but it will generate a map of records identified by a person.

```
PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
PREFIX dwciri: <http://rs.tdwg.org/dwc/iri/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
SELECT ?occurrence ?identifiedBy ?geometry ?catalogNumber ?year
WHERE {
  # Bionomia's curated ORCID -> specimen links, from Koetai. 
  # The LIMIT forces QLever to materialise this before joining; without it the 
  # planner scans all of GBIF for each predicate and asks for 28 GB. 
  {
    SELECT ?occurrence
    WHERE {
      SERVICE <https://koetai.bionames.org/u/0000-0002-7101-9767/bionomia/sparql> {
        ?occurrence dwciri:identifiedBy <https://orcid.org/0000-0001-7750-3480> .
      }
    }
    LIMIT 1000
  } 
  # GBIF's own record for those specimens.
  ?occurrence wdt:P625 ?geometry .
  OPTIONAL {
    ?occurrence dwc:identifiedBy ?identifiedBy
  }
  OPTIONAL {
    ?occurrence dwc:catalogNumber ?catalogNumber
  }
  OPTIONAL {
    ?occurrence dwc:year ?year
  }
}
```

