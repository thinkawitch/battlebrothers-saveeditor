# Simple editor for Battle brothers saves.
Made on php, windows for game version 1.1.0.6 GOG. 

With the help of original steam guide https://steamcommunity.com/sharedfiles/filedetails/?id=598903989


First you need to edit editor.php file and set your values for $loadFromFile, $saveToFile, $brothersCount.
Brothers count is total number of men in your company.

***Always backup your saved games files!*** 

### List brothers stats
```
php editot.php --list
```

### Set action points for brother
```
php editot.php --set-action-points --brother 1 --points 15
```

### Set stats for brother
```
php editot.php --set-stats --brother 1 --stats "100 70 120 95 95 75 75 122"
```

Stats are space separated values of: hitpoints resolve fatigue melee.skill range.skill melee.defence range.defence initiative.
