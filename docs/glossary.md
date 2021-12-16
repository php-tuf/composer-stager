# Project Glossary

* [Active directory](#active-directory)
* [Begin](#begin)
* [Clean](#clean)
* [Commit](#commit)
* [Staging directory](#staging-directory)
* [Stage](#stage)

## Active directory

The Composer root of the live application. cf. [staging directory](#staging-directory).

## Begin

To start the total staging process by copying the [active directory](#active-directory) to the [staging directory](#staging-directory). cf. [stage](#stage), [commit](#commit), and [clean](#clean).

## Clean

To completely remove the [staging directory](#staging-directory). cf. [begin](#begin), [stage](#stage), and [commit](#commit).

## Commit

To make staged changes live by syncing the [active directory](#active-directory) with the [staging directory](#staging-directory) (or syncing the staging directory _to_ the active directory, if you prefer). cf. [begin](#begin), [stage](#stage), and [clean](#clean).

## Staging directory

A non-live copy of the [active directory](#active-directory) where changes can be [staged](#staging) before being [committed](#committing) back.

## Stage

To perform one or more commands on the [staging directory](#staging-directory). cf. [begin](#begin), [commit](#commit), and [clean](#clean).

---

[Back to the README](README.md)
