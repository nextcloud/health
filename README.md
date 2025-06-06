# Farewell to the Nextcloud Health App 😢

## ⚠️Important Notice:  The Nextcloud Health App is currently unmaintained and may be subject to security risks.  

With this statement, I would like to inform you that I will be discontinuing development of the Nextcloud Health App. 

It has been a great pleasure creating this app and your interest in it has made me very happy! I am proud of what has been achieved. However, due to various circumstances, it is currently not possible for me to further develop the app. The development is time-consuming and the basis of the app, which originated as a learning project, is not optimal. There are security vulnerabilities and unprofessional architectural solutions that urgently need to be addressed. 

**👍Good news:  Most of the features of the Nextcloud Health App can now be used in the new "Nextcloud Tables" app. Analyses can then be performed via "Nextcloud Analytics". Data can be exported as CSV and imported into the Tables app.
Take a look on [Medium tutorial](https://medium.com/@deandreamatias/migrate-nextcloud-health-to-tables-and-analytics-542cfb63cff2).**

This app is currently looking for a new maintainer.

❓Interested?  Please feel free to contact me at 📧 dev@d10t.de ! 

Thank you for your understanding.

Cheers

---

# Nextcloud Health
### Track your health privately.

Track your health data within the following provided modules:
- Weight
- Feeling
- Measurement
- Sleep
- Smoking
- Activities
- Medication

![Health - Track your personal health data privately.](https://raw.githubusercontent.com/nextcloud/health/main/screenshots/health-weight.png "Track weight")

## Issues
### Security
If you found a security related issue, please don't create an issue on github. But please write me an mail at dev@d10t.de. That way we can take this into account without telling an attacker where to look for.

### Bugs
You found a bug? Great, please create here an issue for it: [https://github.com/nextcloud/health/issues/new?assignees=&labels=bug%2C0.+Needs+triage&projects=&template=bug-report.yml](https://github.com/nextcloud/health/issues/new?assignees=&labels=bug%2C0.+Needs+triage&projects=&template=bug-report.yml)
If you don't have a Github account feeld free to send me an mail to dev@d10t.de.

### Feature requests
Feels free to create issues for feature requests. But first have a look if there is already a issue for it by searching for this. Second, this is a more or less free time project. So don't expect that we will implement this by time or at all.
https://github.com/nextcloud/health/issues/new?assignees=&labels=enhancement%2C0.+Needs+triage&projects=&template=feature-request.yml

## Installation/Update
The app can be installed through the app store within Nextcloud. You can also download the latest release from the [release page](https://github.com/nextcloud/health/releases).

## Developing

You will need a Nextcloud server running, the individual options are described below.

### General build instructions

General build instructions for the app itself are the same for all options.

To build you will need to have [Node.js](https://nodejs.org/en/) and [Composer](https://getcomposer.org/) installed.

- Install PHP dependencies: `composer install --no-dev`
- Install JS dependencies: `npm ci`
- Build JavaScript for the frontend
	- Development build `npm run dev`
	- Watch for changes `npm run watch`
	- Production build `npm run build`

### GitHub Codespaces / VS Code devcontainer

- Open code spaces or the repository in VS Code to start the dev container
- The container will automatically install all dependencies and build the app
- Nextcloud will be installed from the master development branch and be available on a port exposed by the container

### Running tests
You can use the provided Makefile to run all tests by using:

    make test

### Documentation

The documentation for our REST API can be found at [https://github.com/nextcloud/health/wiki/API](https://github.com/nextcloud/health/wiki/API)

## Contribution Guidelines

Please read the [Code of Conduct](https://nextcloud.com/community/code-of-conduct/). This document offers some guidance to ensure Nextcloud participants can cooperate effectively in a positive and inspiring atmosphere, and to explain how together we can strengthen and support each other.

For more information please review the [guidelines for contributing](https://github.com/nextcloud/server/blob/master/.github/CONTRIBUTING.md) to this repository.

### Apply a license

All contributions to this repository are considered to be licensed under
the GNU AGPLv3 or any later version.

Contributors to the Deck app retain their copyright. Therefore we recommend
to add following line to the header of a file, if you changed it substantially:

```
@copyright Copyright (c) <year>, <your name> (<your email address>)
```

For further information on how to add or update the license header correctly please have a look at [our licensing HowTo][applyalicense].

### Sign your work

We use the Developer Certificate of Origin (DCO) as a additional safeguard
for the Nextcloud project. This is a well established and widely used
mechanism to assure contributors have confirmed their right to license
their contribution under the project's license.
Please read [developer-certificate-of-origin][dcofile].
If you can certify it, then just add a line to every git commit message:

````
  Signed-off-by: Random J Developer <random@developer.example.org>
````

Use your real name (sorry, no pseudonyms or anonymous contributions).
If you set your `user.name` and `user.email` git configs, you can sign your
commit automatically with `git commit -s`. You can also use git [aliases](https://git-scm.com/book/tr/v2/Git-Basics-Git-Aliases)
like `git config --global alias.ci 'commit -s'`. Now you can commit with
`git ci` and the commit will be signed.

[dcofile]: https://github.com/nextcloud/server/blob/master/contribute/developer-certificate-of-origin
[applyalicense]: https://github.com/nextcloud/server/blob/master/contribute/HowToApplyALicense.md
