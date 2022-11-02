
cp -rf /data/html/Prod/KBCommons_multi/resources/views/system/tools/AlleleCatalogTool2 /home/chanye/projects/

mkdir -p /home/chanye/projects/AlleleCatalogTool2/controller
mkdir -p /home/chanye/projects/AlleleCatalogTool2/routes

cp -rf /data/html/Prod/KBCommons_multi/app/Http/Controllers/System/Tools/KBCToolsAlleleCatalogTool2Controller.php /home/chanye/projects/AlleleCatalogTool2/controller/

cp -rf /data/html/Prod/KBCommons_multi/public/system/home/AlleleCatalogTool2/* /home/chanye/projects/AlleleCatalogTool2/

grep "AlleleCatalogTool2" /data/html/Prod/KBCommons_multi/routes/web.php > /home/chanye/projects/AlleleCatalogTool2/routes/web.php
