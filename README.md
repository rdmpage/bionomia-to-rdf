# Bionomia to RDF

Convert Bionomia data export to triples. The latest version of the data is on Zenodo https://doi.org/10.5281/zenodo.13937805, and comprises two CSV files: bionomia-public-claims.csv, and bionomia-public-profiles.csv. Data files are in the data directory in folders labelled by the Zenodo record number (for example 21734026).

bionomia-public-claims.csv has three columns Subject, Predicate, and Object, so is close to [N-Triples](https://en.wikipedia.org/wiki/N-Triples). The GBIF occurrence URL is https://gbif.org which differs from the https://www.gbif.org form used by GBIF RDF.

bionomia-public-profiles.csv has columns Family, Given, Particle, 	OtherNames	, LabelName, Country, Keywords, wikidata, ORCID, URL which requires mapping to a vocabulary.

