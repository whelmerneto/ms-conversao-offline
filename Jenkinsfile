#!groovy
  
@Library('movida') _

node {
    try {
        movida.buildAndApply()
    } catch (err) {
        movida.catchTopLevelError(err)
    } finally {
        movida.topLevelFinally()
    }
}
