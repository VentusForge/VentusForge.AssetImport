# Neos Asset Importer

Import a resource as an asset through the command line.

## Installation

```bash
composer require ventusforge/neos-asset-import
```

## Usage

You have to set the type of the imported asset. The possible types are `image`, `video`, `audio` and `document`.

```bash
./flow assetimport:import
  --resource "/path/to/resource"
  --title "Title of the asset"
  --caption "Caption of the asset"
  --copyright-notice "copyright notice of the asset"
  --filename "override-filename.png"
```

NOTE: You have to set the `filename` argument if you are importing a remote resource.
