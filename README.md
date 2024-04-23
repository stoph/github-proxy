# Github Proxy
This is a simple proxy for the Github's archive files. It's intended to be used as a demo for an experiment with [WordPress Playground](https://wordpress.org/playground/).


---
# Examples

## Full Branch:
Timing:  *n/a* (*Passthrough from GitHub*)
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&branch=try/drop-image-on-empty-block`

**Code executed**

`https://github.com/WordPress/gutenberg/archive/try/drop-image-on-empty-block.zip`

## Full PR:
Timing: *2.78s*
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&pr=60903`

**Code executed**

```shell
git clone --depth 1 --filter=blob:none --no-checkout https://github.com/WordPress/gutenberg.git
cd gutenberg
git fetch origin pull/60903/head
git checkout FETCH_HEAD
```

## Full Commit:
Timing: *n/a* (*Passthrough from GitHub*)
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&commit=adfa3c6`

**Code executed**

`https://github.com/WordPress/gutenberg/archive/adfa3c6.zip`
--OR--
```shell
git clone --filter=blob:none --no-checkout https://github.com/WordPress/gutenberg.git
cd gutenberg
git checkout adfa3c6
```

## Partial Branch:
Timing: *0.18s*
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&directory=packages/block-editor/src/components/use-block-drop-zone&branch=try/drop-image-on-empty-block`

**Code executed**

```shell
git clone --depth 1 --filter=blob:none --sparse --no-checkout --branch try/drop-image-on-empty-block https://github.com/WordPress/gutenberg.git
cd gutenberg
git config core.sparseCheckout true
git sparse-checkout set packages/block-editor/src/components/use-block-drop-zone --no-cone
git read-tree -mu HEAD
```

## Partial PR:
Timing: *1.91s*
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&directory=.github/workflows&pr=60903`

**Code executed**

```shell
git clone --depth 1 --filter=blob:none --sparse --no-checkout https://github.com/WordPress/gutenberg.git
cd gutenberg
git config core.sparseCheckout true
git sparse-checkout set .github/workflows --no-cone
git fetch origin pull/60903/head
git checkout FETCH_HEAD
```

## Partial Commit:
Timing: *4.09s*
`https://github-proxy.com/proxy?repo=WordPress/gutenberg&directory=packages/components&commit=8ff4af6`

**Code executed**

```shell
git clone --filter=blob:none --sparse --no-checkout https://github.com/WordPress/gutenberg.git
cd gutenberg
git config core.sparseCheckout true
git sparse-checkout set packages/components --no-cone
git checkout 8ff4af6
```
