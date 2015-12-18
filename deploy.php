<?php
define('SEMANTIC_VERSION_REGEX', '/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/');

function increaseVersion($version, $mode) {
	preg_match(SEMANTIC_VERSION_REGEX, $version, $versionComponents);
	unset($versionComponents[0]);
	switch ($mode) {
		case 'patch':
			$versionComponents[3]++;
			break;
		case 'minor':
			$versionComponents[2]++;
			$versionComponents[3] = 0;
			break;
		case 'major':
			$versionComponents[1]++;
			$versionComponents[2] = 0;
			$versionComponents[3] = 0;
			break;
	}
	return implode('.', $versionComponents);
}

function getComposerMetadata() {
	$composerMetadata = json_decode(file_get_contents('composer.json'));
	$composerMetadata->version = isset($composerMetadata->version) ? $composerMetadata->version : '0.0.0';
	return $composerMetadata;
}

function getGithubCurl() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, 'Release Script for ' . get('repository'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERPWD, get('username') . ':' . get('password'));
	return $ch;
}

task('release:askPasswort', function(){
	$password = askHiddenResponse('Passwort for ' . get('username') . ':');
	set('password', $password);
});

task('release:increaseVersionPatch', function () {
	$composerMetadata = getComposerMetadata();
	$composerMetadata->version = increaseVersion($composerMetadata->version, 'patch');
	set('version', $composerMetadata->version);
	file_put_contents('composer.json', json_encode($composerMetadata, JSON_PRETTY_PRINT));
});

task('release:increaseVersionMinor', function () {
	$composerMetadata = getComposerMetadata();
	$composerMetadata->version = increaseVersion($composerMetadata->version, 'minor');
	set('version', $composerMetadata->version);
	file_put_contents('composer.json', json_encode($composerMetadata, JSON_PRETTY_PRINT));
});

task('release:increaseVersionMajor', function () {
	$composerMetadata = getComposerMetadata();
	$composerMetadata->version = increaseVersion($composerMetadata->version, 'major');
	set('version', $composerMetadata->version);
	file_put_contents('composer.json', json_encode($composerMetadata, JSON_PRETTY_PRINT));
});

task('release:fetchVersion', function () {
	$composerMetadata = getComposerMetadata();
	set('version', $composerMetadata->version);
});

task('release:commitComposer', function () {
	// writeln('Update and commit version in composer.json');
	runLocally('git add composer.json');
	runLocally('git commit -m "' . get('version') . '"');
});

task('release:tagRelease', function () {
	// writeln('tag current state with provided version number');
	runLocally('git tag "' . get('version') . '"');
});

task('release:pushTags', function () {
	// writeln('push tags to github');
	runLocally('git push origin master');
	runLocally('git push origin --tags');
});

task('release:removeCurrentTagFromRemote', function () {
	runLocally('git tag -d "' . get('version') . '"');
	runLocally('git push origin :refs/tags/' . get('version'));
});

task('release:createGithubRelease', function() {
	$ch = getGithubCurl();

	$release = array(
		'tag_name' => get('version'),
		'name' => 'Release: ' . get('version')
	);
	$uri = 'https://api.github.com/repos/' . get('repository') . '/releases';
	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($release));
	curl_setopt($ch, CURLOPT_POST, 1);

	$release = json_decode(curl_exec($ch));
	$releaseId = $release->id;
	set('releaseId', $releaseId);
});

task('release:destroyGithubRelease', function() {
	$ch = getGithubCurl();

	$uri = 'https://api.github.com/repos/' . get('repository') . '/releases/tags/' . get('version');
	curl_setopt($ch, CURLOPT_URL, $uri);

	$release = json_decode(curl_exec($ch));
	$releaseId = $release->id;

	$uri = 'https://api.github.com/repos/' . get('repository') . '/releases/' . $releaseId;
	$ch = getGithubCurl();
	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_exec($ch);
});

set('username', 'mneuhaus');
set('repository', 'mia3/koseki');

task('release:patch', [
	'release:askPasswort',
    'release:increaseVersionPatch',
    'release:commitComposer',
    'release:tagRelease',
    'release:pushTags',
    'release:createGithubRelease'
]);

task('release:minor', [
	'release:askPasswort',
    'release:increaseVersionMinor',
    'release:commitComposer',
    'release:tagRelease',
    'release:pushTags',
    'release:createGithubRelease'
]);

task('release:major', [
	'release:askPasswort',
    'release:increaseVersionMajor',
    'release:commitComposer',
    'release:tagRelease',
    'release:pushTags',
    'release:createGithubRelease'
]);

task('release:replaceCurrent', [
	'release:askPasswort',
    'release:fetchVersion',
    'release:destroyGithubRelease',
    'release:removeCurrentTagFromRemote',
    'release:tagRelease',
    'release:pushTags',
    'release:createGithubRelease'
]);
