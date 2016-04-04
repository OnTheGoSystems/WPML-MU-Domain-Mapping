### WPML Core Tests

Running the tests is very easy.

# Go into the tests folder in the project root.

```$ cd tests```

# Install the test database

```$ ./install-wp-tests.sh {dbname} {dbuser} {dbpassword} {dbhost} {version-number|latest}```

Please use a database name, that doesn't yet exist in your server. 
The script will create the database for you and download all necessary test libraries.
If you database password is nothing then just put two quotes ```""``` for it :)
The database user must have the rights to create new databases.

*Unless otherwise specified, you only need to run this script once, to setup the testing environment.*

# Running tests

## With coverage (slow)
Running the tests **with coverage** is trivial, simply go to the project root and run

```$ phpunit ```

This will generate coverage data in the clover .xml format. If you want some more readable format, you can type

```$ phpunit --coverage-html tmp``` 

This will create a nicely readable html report in tests/tmp (which for this reason has been excluded in the `.gitignore`).
Be warned though, depending on the performance of this setup, running tests with coverage can take around 5 minutes at the moment of writing this.

## Without coverage (fast)

To run tests without coverage for quick development simply run

```$ phpunit -c nocoverage.xml```

from the project root. This should only take about 30s for complete run.

## Running individual tests with PHPStorm/Idea

To run individual tests you first have to [configure phpunit in your IDE](https://www.jetbrains.com/phpstorm/help/testing.html).

After that, simply set the `nocoverage.xml` as your default *phpunit* config file for the project.

Once that is done you should be able to just right click on every test function you want to run and run it individually.

You can also right click on `nocoverage.xml` and select **Run 'nocoverage.xml'** to run all tests.

The same applies if you want to run all tests with coverage, by selecting the `phpunit.xml` file instead.
