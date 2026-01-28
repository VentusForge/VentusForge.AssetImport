# Neos Asset Importer

Import a resource as an asset through the command line.

## Installation

```bash
composer require ventusforge/neos-asset-import
```

## Usage

### Import a single file

```bash
./flow assetimport:file --resource "/path/to/resource.jpg"
```

Optional arguments:

- `--title`: Title of the asset
- `--caption`: Caption of the asset
- `--copyright`: Copyright notice of the asset
- `--filename`: "override-filename.jpg" :warning: the name must contain the file extension!
- `--dry-run`: If true, the files will not be imported, but the command will output what would have been imported
