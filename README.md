Engineer Assignment
===================

Implement a working SDK based on the `IClient` interface.

The goal is to produce well-written code that you are proud to commit.
Remember to comment (but not over-comment) your code.
The comments should be for other developers, just as in an real-life scenario.

If you need to comment on your solution to the assignment (by all means do),
please keep that in a seperate document.
We prefer markdown or other plain-text formats, but a PDF is also fine.
Note that Microsoft Word documents (or other office suite tools) will not be read.
Export to PDF if you have to use such a tools.



Getting Started
-----------------------

Simply clone or fork this repository, and commit your work directly to it.
When it is time to hand in the solution, see the "Hand In" section.



What we are looking for
-----------------------

- Maintainability
- Good structure (code as well as files/folders)
- Well-commented public interface

If you choose performance over readability for certain parts, please write a few paragraphs
on why you choose to optimize that part of your code (again, not in the comments).



Requirements
-----------------------

You must implement the methods `initialize`, `signIn`, `signOut` and `destroy`.
The remaining methods are optional, but we would like at least one of them
to be functional.
PHPDoc is left out on the remaining functions on purpose. It is up to you
to design the best suited return values.

You can change the `IClient` interface if you write a few lines about why you changed it.



Extra Credits
-----------------------

- Unit tests is a major plus. We suggest [https://phpunit.de](https://phpunit.de/), but any framework (or no framework) is fine.
- Code coverage report.
- Few as possible external dependencies.



Hand In
-----------------------

If you push your code to github or bitbucket, you can simply send us a link to the repo.

If you prefer to keep the implementation private, zip the entire project directory (.git folder included!)
and send us the archive.
Note that we only accept `.zip` and `.tar.gz` archives.



Final Notes
-----------------------

- Don't rush it. We would rather see an incomplete good implementation, than a complete bad one.
- Consider that you're implementing an SDK for other developers to use; your design decisions should reflect this.

